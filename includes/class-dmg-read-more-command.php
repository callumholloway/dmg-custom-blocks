<?php
if (!class_exists( 'WP_CLI')) {
    return;
}
class DMG_Read_More_Command{
    /**
     * Search for posts containing the Read More block within a date range.
     *
     * ## OPTIONS
     *
     * [--date-before=<date>]
     * : Upper date limit (Y-m-d). Optional.
     *
     * [--date-after=<date>]
     * : Lower date limit (Y-m-d). Optional.
     *
     * ## EXAMPLES
     *
     *     wp dmg-read-more search
     *     wp dmg-read-more search --date-after=2025-01-01 --date-before=2025-02-01
     * 
     * @param array $args
     * @param array $assoc_args
     * @return void 
     */
    public function search($args, $assoc_args){
        // Start timing the operation
        $start_time = microtime(true);
        
        // Default to current date for upper bound
        $date_before = isset($assoc_args['date-before']) ? $assoc_args['date-before'] : current_time('Y-m-d');
        
        // Default to 30 days ago from today (including today)
        $date_after = isset($assoc_args['date-after']) 
            ? $assoc_args['date-after'] 
            : date('Y-m-d', strtotime('-29 days')); // 29 days ago to include today in the 30-day range

        // Validate dates
        if(!$this->validate_date($date_before) || !$this->validate_date($date_after)){
            WP_CLI::error("Invalid date format. Use Y-m-d.");
            return;
        }
        
        // Get current date for validation
        $current_date = current_time('Y-m-d');
        
        // various date checks and error handling
        if (strtotime($date_before) > strtotime($current_date)) {
            WP_CLI::error("The 'date-before' parameter cannot be in the future. Current date is {$current_date}.");
            return;
        }
        
        if (strtotime($date_after) > strtotime($current_date)) {
            WP_CLI::error("The 'date-after' parameter cannot be in the future. Current date is {$current_date}.");
            return;
        }
        
        // Check if date range is valid (after date should be before before date)
        if (strtotime($date_after) > strtotime($date_before)) {
            WP_CLI::error("The 'date-after' parameter must be earlier than the 'date-before' parameter.");
            return;
        }

        // Provide clear feedback about the date range and what is being searched
        if (isset($assoc_args['date-after']) || isset($assoc_args['date-before'])) {
            WP_CLI::log("Searching posts from $date_after to $date_before in batches of 1000...");
        } else {
            WP_CLI::log("Searching posts from the last 30 days ($date_after to $date_before) in batches of 1000...");
        }

        // Set up for batch processing
        $posts_per_page = 1000; // Increased for better performance
        $paged = 1; // Initialize paged variable
        $total_processed = 0;
        $total_found = 0;
        $posts_data = [];
        $completed = false;
        
        // Search patterns
        $search_patterns = [
            '<!-- wp:dmg/read-more',  // Block comment version
            'class="dmg-read-more"',  // Class-based detection - we'd maybe want to lose this if this class was used elsewhere
        ];
        
        // Process posts in batches
        while (!$completed) {
            // Set up some optimised WP_Query args
            $query_args = [
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $posts_per_page,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC',
                'date_query' => [
                    [
                        'after' => $date_after,
                        'before' => $date_before,
                        'inclusive' => true,
                    ],
                ],
                // Performance optimisations - done stuff like this before on sites with lots of posts running scheduled tasks on CRONs etc.
                'cache_results' => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'no_found_rows' => true
            ];
            
            // First pass: Get all post IDs that might contain the pattern (faster pre-filtering)
            $prefilter_query = new WP_Query($query_args);
            
            if (empty($prefilter_query->posts)) {
                $completed = true;
                continue;
            }
            
            $batch_size = count($prefilter_query->posts);
            $total_processed += $batch_size;
            $batch_matches = 0;
            
            // For each post that passed the pre-filter, check if it actually contains our pattern
            foreach ($prefilter_query->posts as $post) {
                $has_read_more = false;
                $post_content = $post->post_content;
                
                foreach ($search_patterns as $pattern) {
                    if (strpos($post_content, $pattern) !== false) {
                        $has_read_more = true;
                        break;
                    }
                }
                
                if ($has_read_more) {
                    $batch_matches++;
                    $total_found++;
                    
                    // Get additional post data only for matches
                    $author_name = get_the_author_meta('display_name', $post->post_author);
                    
                    $posts_data[] = [
                        'ID' => $post->ID,
                        'Title' => wp_strip_all_tags($post->post_title),
                        'Date' => get_date_from_gmt($post->post_date, get_option('date_format')),
                        'Author' => $author_name,
                        'URL' => get_permalink($post->ID)
                    ];
                }
            }
            
            // Progress feedback
            WP_CLI::log(sprintf(
                "Batch complete: %d posts processed, %d matches found (running totals: %d processed, %d matches)", 
                $batch_size, 
                $batch_matches, 
                $total_processed,
                $total_found
            ));
            
            // Move to the next page
            $paged++;
            
            // Check if we've reached the end - with no_found_rows true, we need to check batch size
            if ($batch_size < $posts_per_page) {
                $completed = true;
            }
        }
        
        // Calculate execution time for information
        $execution_time = microtime(true) - $start_time;
        $formatted_time = $this->format_time($execution_time);
        
        if($total_found > 0){
            // More detailed success message
            WP_CLI::success(sprintf(
                "Search complete in %s.\nTotal posts processed: %d\nMatching posts found: %d (%.2f%% of processed posts)",
                $formatted_time,
                $total_processed,
                $total_found,
                ($total_found / $total_processed) * 100
            ));
            
            // Display nice formatted table with results
            WP_CLI\Utils\format_items(
                'table',
                $posts_data,
                ['ID', 'Title', 'Date', 'Author', 'URL']
            );
        } else{
            WP_CLI::log(sprintf(
                "No matching posts found. Processed %d posts in %s.",
                $total_processed,
                $formatted_time
            ));
        }
    }
    
    /**
    * Format execution time in a human-readable way
    * @param float $seconds
    * @return string
    */
    private function format_time($seconds) {
        if ($seconds < 1) {
            return sprintf("%.2f milliseconds", $seconds * 1000);
        } else if ($seconds < 60) {
            return sprintf("%.2f seconds", $seconds);
        } else {
            $minutes = floor($seconds / 60);
            $seconds = $seconds % 60;
            return sprintf("%d minutes, %.2f seconds", $minutes, $seconds);
        }
    }
    
    /**
    * Validate date format (Y-m-d)
    * @param string $date
    * @return bool
    */
    private function validate_date($date){
        // Try to create a DateTime object from the input string
        $potential_date = DateTime::createFromFormat('Y-m-d',$date);
        // Return true only if:
        // 1. $potential_date is a valid DateTime object (not false)
        // 2. Converting back to string matches original input exactly
        return $potential_date && $potential_date->format('Y-m-d') === $date;
    }
}
