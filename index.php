<?php
/**
 * Plugin Name: Custom Block Registraion and CLI Command
 * Description: A plugin which includes a block constructed with native WP React Tools to allow editors to search for and choose a published post to link to.
 *              Supports multiple blocks being created later if needed.
 *              Also includes a custom WP CLI command that can find all instances of the first block (including within a user defined date range) and STDOUT the results.
 * 
 * Version: 1.0
 * Author: Callum Holloway
 */

// Exit if accessed directly.
if (!defined('ABSPATH'))
    exit;

// Register all block assets.
function dmg_register_all_blocks() {
	$blocks_dir = __DIR__ . '/blocks';

	foreach (glob("$blocks_dir/*/block.json") as $block_json) {
		register_block_type(dirname($block_json));
	}
}
add_action('init', 'dmg_register_all_blocks');



// Load up the custom command if WP CLI is available
if (defined('WP_CLI') && WP_CLI ) {
    require_once __DIR__ . '/includes/class-dmg-read-more-command.php';
    WP_CLI::add_command( 'dmg-read-more', 'DMG_Read_More_Command' );
}