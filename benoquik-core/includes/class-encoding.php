<?php
/**
 * Repairs "mojibake": UTF-8 text that was imported through a latin1 / cp1252
 * database connection and so got encoded twice ("—" stored as "â€”").
 *
 * How it works (and why it is serialization-safe):
 * every double-encoded character is a run like "â€”" whose characters map
 * one-to-one back to the original UTF-8 bytes (E2 80 94). We turn each run
 * back into those bytes, which restores the *exact* original string — so the
 * byte lengths inside serialized values (s:35:"…") become correct again.
 * Every serialized value is still checked with unserialize() after the fix;
 * if one is still broken its string lengths are recounted as a last resort.
 *
 * @package BenoquikCore
 */

namespace Benoquik;

defined( 'ABSPATH' ) || exit;

class Encoding {

	/** Windows-1252 characters for bytes 0x80–0x9F (as used by MySQL "latin1"). */
	const CP1252 = array(
		0x20AC => 0x80, 0x201A => 0x82, 0x0192 => 0x83, 0x201E => 0x84, 0x2026 => 0x85,
		0x2020 => 0x86, 0x2021 => 0x87, 0x02C6 => 0x88, 0x2030 => 0x89, 0x0160 => 0x8A,
		0x2039 => 0x8B, 0x0152 => 0x8C, 0x017D => 0x8E, 0x2018 => 0x91, 0x2019 => 0x92,
		0x201C => 0x93, 0x201D => 0x94, 0x2022 => 0x95, 0x2013 => 0x96, 0x2014 => 0x97,
		0x02DC => 0x98, 0x2122 => 0x99, 0x0161 => 0x9A, 0x203A => 0x9B, 0x0153 => 0x9C,
		0x017E => 0x9E, 0x0178 => 0x9F,
	);

	/** MySQL latin1 keeps these five undefined cp1252 bytes as control characters. */
	const RAW_CONTROLS = array( 0x81, 0x8D, 0x8F, 0x90, 0x9D );

	private static $pattern = null;

	private static function pattern() {
		if ( null === self::$pattern ) {
			$cont = '\x{80}-\x{BF}';
			foreach ( array_keys( self::CP1252 ) as $cp ) {
				$cont .= sprintf( '\x{%X}', $cp );
			}
			$c             = '[' . $cont . ']';
			self::$pattern = '/[\x{C2}-\x{DF}]' . $c . '|[\x{E0}-\x{EF}]' . $c . '{2}|[\x{F0}-\x{F4}]' . $c . '{3}/u';
		}
		return self::$pattern;
	}

	private static function byte_of( $cp ) {
		if ( $cp >= 0xA0 && $cp <= 0xFF ) {
			return $cp;
		}
		if ( isset( self::CP1252[ $cp ] ) ) {
			return self::CP1252[ $cp ];
		}
		if ( in_array( $cp, self::RAW_CONTROLS, true ) ) {
			return $cp;
		}
		return null;
	}

	/** Does this string contain double-encoded sequences? */
	public static function is_broken( $s ) {
		return is_string( $s ) && '' !== $s && mb_check_encoding( $s, 'UTF-8' ) && preg_match( self::pattern(), $s ) && self::fix_text( $s ) !== $s;
	}

	/** Fix one string (repeats up to 3 times to undo triple encoding). */
	public static function fix_text( $s ) {
		if ( ! is_string( $s ) || '' === $s || ! mb_check_encoding( $s, 'UTF-8' ) ) {
			return $s;
		}
		for ( $i = 0; $i < 3; $i++ ) {
			$new = preg_replace_callback(
				self::pattern(),
				static function ( $m ) {
					$bytes = '';
					foreach ( mb_str_split( $m[0], 1, 'UTF-8' ) as $ch ) {
						$b = self::byte_of( mb_ord( $ch, 'UTF-8' ) );
						if ( null === $b ) {
							return $m[0];
						}
						$bytes .= chr( $b );
					}
					return mb_check_encoding( $bytes, 'UTF-8' ) ? $bytes : $m[0];
				},
				$s
			);
			if ( null === $new || $new === $s ) {
				break;
			}
			$s = $new;
		}
		return $s;
	}

	/**
	 * Fix a raw database value. Returns [ new_value, note ] where note is
	 * '' (fine), 'recounted' (serialized lengths had to be recalculated) or
	 * 'broken-serialized' (still not unserializable; left as fixed text).
	 */
	public static function fix_db_value( $value ) {
		$fixed = self::fix_text( $value );
		if ( $fixed === $value || ! is_serialized( $fixed ) ) {
			return array( $fixed, '' );
		}
		if ( false !== @unserialize( $fixed ) || 'b:0;' === $fixed ) { // phpcs:ignore
			return array( $fixed, '' );
		}
		$recounted = self::recount_serialized( $fixed );
		if ( false !== @unserialize( $recounted ) ) { // phpcs:ignore
			return array( $recounted, 'recounted' );
		}
		return array( $fixed, 'broken-serialized' );
	}

	/** Recalculate s:N:"…"; lengths in a serialized string. */
	public static function recount_serialized( $s ) {
		return preg_replace_callback(
			'/s:(\d+):"(.*?)";(?=[sibdaON]:|N;|\}|$)/s',
			static fn( $m ) => 's:' . strlen( $m[2] ) . ':"' . $m[2] . '";',
			$s
		);
	}

	/** All tables of this WordPress install (by table prefix). */
	public static function tables() {
		global $wpdb;
		$like = $wpdb->esc_like( $wpdb->prefix ) . '%';
		return $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) );
	}

	/**
	 * Scan (and optionally fix) every text column of every table.
	 *
	 * @param bool          $dry_run Only count.
	 * @param callable|null $log     Receives progress lines.
	 * @return array Totals.
	 */
	public static function repair_database( $dry_run = false, $log = null ) {
		global $wpdb;
		$log    = $log ? $log : static function () {};
		$totals = array( 'rows' => 0, 'values' => 0, 'recounted' => 0, 'broken' => 0, 'skipped_tables' => array() );

		foreach ( self::tables() as $table ) {
			$cols = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A ); // phpcs:ignore
			$pk   = array();
			$text = array();
			foreach ( $cols as $c ) {
				if ( 'PRI' === $c['Key'] ) {
					$pk[] = $c['Field'];
				}
				if ( preg_match( '/char|text/i', $c['Type'] ) ) {
					$text[] = $c['Field'];
				}
			}
			if ( ! $text ) {
				continue;
			}
			if ( ! $pk ) {
				$totals['skipped_tables'][] = $table;
				continue;
			}
			// Pre-filter: double-encoded text always contains the byte C3 (the UTF-8 lead byte of Â..ô).
			$where = array();
			foreach ( $text as $col ) {
				$where[] = "BINARY `{$col}` LIKE CONCAT('%', CHAR(0xC3), '%')";
			}
			$select = '`' . implode( '`,`', array_unique( array_merge( $pk, $text ) ) ) . '`';
			$rows   = $wpdb->get_results( "SELECT {$select} FROM `{$table}` WHERE " . implode( ' OR ', $where ), ARRAY_A ); // phpcs:ignore
			$changed_in_table = 0;
			foreach ( (array) $rows as $row ) {
				$update = array();
				foreach ( $text as $col ) {
					if ( ! isset( $row[ $col ] ) || ! is_string( $row[ $col ] ) ) {
						continue;
					}
					list( $new, $note ) = self::fix_db_value( $row[ $col ] );
					if ( $new !== $row[ $col ] ) {
						$update[ $col ] = $new;
						++$totals['values'];
						if ( 'recounted' === $note ) {
							++$totals['recounted'];
						} elseif ( 'broken-serialized' === $note ) {
							++$totals['broken'];
							$log( "  ! still-broken serialized value in {$table}.{$col}" );
						}
					}
				}
				if ( $update ) {
					++$totals['rows'];
					++$changed_in_table;
					if ( ! $dry_run ) {
						$where_pk = array();
						foreach ( $pk as $k ) {
							$where_pk[ $k ] = $row[ $k ];
						}
						$wpdb->update( $table, $update, $where_pk );
					}
				}
			}
			if ( $changed_in_table ) {
				$log( sprintf( '  %s: %d row(s)%s', $table, $changed_in_table, $dry_run ? ' would be fixed' : ' fixed' ) );
			}
		}
		return $totals;
	}

	/** Count remaining "â€" / "Ã" / "Â" sequences in the database. */
	public static function count_remaining() {
		global $wpdb;
		$needles = array( 'â€', 'Ã', 'Â' );
		$count   = array();
		foreach ( $needles as $n ) {
			$count[ $n ] = 0;
		}
		foreach ( self::tables() as $table ) {
			$cols = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A ); // phpcs:ignore
			foreach ( $cols as $c ) {
				if ( ! preg_match( '/char|text/i', $c['Type'] ) ) {
					continue;
				}
				foreach ( $needles as $n ) {
					$count[ $n ] += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE BINARY `{$c['Field']}` LIKE %s", '%' . $wpdb->esc_like( $n ) . '%' ) ); // phpcs:ignore
				}
			}
		}
		return $count;
	}
}
