# Gutenberg Block Registration + Search CLI Command
A Gutenberg block which allows an editor to search for a published post to insert as a 'read more' link into a post via the the Gutenberg editor. Also, a corresponding WP CLI command which provides the ability to search for posts which contain that block in the post content. This readme comprises both some installation and functional information as well as some of my thoughts about the work.

## Installation
1. Download the latest release, and either upload the extracted zip to your WordPress instance (into a directoy sited at `wp-content/plugins/dmg-alert`) or install via the plugins screen as a zip file.
2. Activate the plugin via the WordPress plugins screen.


## Block usage
The block can be added via the Gutenberg editor, it's possible to search for a post by keyword or ID, or click through the prev/next pagination to access more posts. It's possible to modify the styling of the block, including independently styling the bg, text, and link (assuming theme.json in theme supports it). It'll inherit any colours defined in theme.json from a parent theme. It would of course be possible to extend this further to include spacing, alignment, font family interfaces if required. I did consider imposing some default styling but I preferred to keep it as agnostic as possible. I simply added a simple css file with a basic hover/underline state to ensure that the link is visually distinguisable for the purposes of WCAG. 

As I have not worked extensively with the core WP React tools - wherever I've worked we've preferred using ACF blocks as a layer of abstraction in the middle - I was very interested in this approach. I was quickly thinking about how to leverage this on a larger scale (as a plugin per block feels a bit onerous) so I spent a little time exploring how this tooling might look in a way where we register multiple blocks in 1 plugin, utilising the webpack.config.js & index.php to iterate through and load all the blocks it can find in the `/blocks` directory. An example second block is included simply because I was proving to myself it worked. 

My understanding from the WordPress codex (referencing the `create-block` tooling) is that the way I've bundled all the block code into the index.js file is (whilst functional) not considered best practice. It'd be better next time to compartmentalise this into `index.js`, and rather import the `edit.js` and `view.js` files. It'd be quick to revise this of course.

## CLI command usage
The CLI command is `wp dmg-read-more search` in the most simplistic form, which will return all posts from the last 30 days including today. 

There are optional options `--date-before` and `--date-after`, so posts can be surfaced in specific date ranges. It is important to pass a date string in the format of `YYYY-MM-DD`.

It'll render all the post ids that match in a table along with some extra information including author, publish date, url. The process will also provide feedback on how many records have been processed and keep running totals to provide user reassurance the process is running (important if it was searching a large dataset). It also logs the execution time of the process and what total % of posts contain the block. I understand that these additions add some load which is particularly important if this was a larger query, and this could be made leaner. Potentially this more comprehensive logging could be toggled via an option. 

The performance considerations did get me thinking because I've not worked with datasets into the millions previously, however I applied some of the optimisation principles in my WP Query that I've used in my career elsewhere to date. I did wonder if a raw wp_db prepare might be faster than a wp_query (if not a bit unsightly), but didn't do any testing to prove or disprove this notion.

Functionally speaking a way I immediately feel this could be be enhanced might be by accepting dates more freely by accepting an entire year rather than confining `YYYY-MM-DD`. Another way is to make it a more generic block search tool, by allowing a block slug to be passed in as an argument. Another way is to add an argument to enable which data types (post types) are to be queried. 

