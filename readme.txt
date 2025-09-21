=== AISee SEO ===
Contributors: varun21, ruchikawp
Tags: seo, google search console, keyword research, internal links, related posts, tag cloud, content analysis, on-page seo
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 2.3
License: MIT
License URI: https://opensource.org/licenses/MIT

GSC-powered keyword insights inside the editor. Auto-tag content, get internal link recommendations, and generate an accurate tag cloud from your rendered page.

== Description ==
AISee SEO turns your live Google Search Console (GSC) data into on-page actions: add relevant terms, improve internal links, and prioritize content refreshes.

Key benefits and how it’s different:

- Google Search Console inside the editor: Fetch clicks, impressions, CTR, and position for the current post’s URL and filter by thresholds (CTR, position, clicks, impressions).
- One-click keyword to taxonomy: Save query phrases as “AISee Terms” and auto-generate single-word “AISee Tags” (minus stop-words) for smarter indexing and linking.
- Internal link recommendations: See suggested posts to link to directly in the Posts/Pages list, based on your AISee tags and phrase similarity.
- “See Also” related posts on the front-end: Append related items to your content automatically, cached for performance.
- Real tag cloud from the rendered page: Analyze the fully rendered HTML (not just post content) to visualize your true keyword landscape, with density/length controls.
- Runs site-wide on a schedule or via WP-CLI: Weekly cron and a CLI command to refresh keywords and populate taxonomies across your site.

Who is it for?

- Site owners, bloggers, and editors who want to use real query data to sharpen on-page focus.
- Agencies/content teams who want faster internal linking and content refresh workflows without leaving WordPress.

How it works

1) Connect to GSC via the AISee service (free setup) to authorize access.  
2) Open any post/page to fetch query metrics for that URL.  
3) Add relevant phrases as AISee Terms/Tags with one click.  
4) Use the Tag Cloud and Internal Links to tune and interlink your content.  
5) Let the weekly cron or CLI keep things fresh at scale.

Important notice

The GSC feature requires a connection to the AISee service to broker OAuth and return query metrics. You’ll be prompted for a one-click free registration and Google authorization. See Privacy for details.

== Installation ==
1. Upload the plugin files to `/wp-content/plugins/aisee-seo/` or install via Plugins → Add New.  
2. Activate the plugin.  
3. Edit any post/page and open the “AiSee Insights from Google Search Console” metabox.  
4. Click “Setup Account” to register and then “Connect with Google Search Console” to authorize.  
5. Use the “Fetch Data” button to load keywords and start tagging.

== Frequently Asked Questions ==

= Do I need a Google Search Console property? =
Yes. You must have a verified GSC property for your site. AISee uses your authorization to fetch query data for the current URL.

= What data does AISee send/receive? =
During setup we send minimal site context and your provided user info to the AISee service to create your account and handle the OAuth flow. The service returns query metrics (clicks, impressions, CTR, position) for the current URL so the plugin can display and filter them. See Privacy for more details.

= Does it work with my SEO plugin (Yoast, Rank Math, etc.)? =
Yes. AISee is complementary and focuses on GSC-powered insights, tagging, and internal linking. Keep your main SEO plugin for metadata, sitemaps, etc.

= Can I disable the “See Also” related posts? =
Yes, developers can remove the filter added by the plugin. For example in a small mu-plugin or theme setup file:

`remove_filter( 'the_content', 'aisee_show_related_posts', 9 );`

= Is there a command-line or automated refresh? =
A weekly cron refresh runs by default. In the licensed version you have access to WP CLI integration.

= Does it support the block editor? =
Yes. The UI is delivered via classic metaboxes and works alongside the block editor. A dedicated block/sidebar panel is planned for future versions.

== Screenshots ==
1. GSC keyword metrics inside the post editor with filters and one-click tagging.  
2. Internal link recommendations column in the Posts list.  
3. Front‑end “See Also” related posts appended to content.  
4. Tag Cloud generated from the fully rendered page HTML.

== Privacy ==
AISee connects your site to the AISee service to broker Google OAuth and return GSC query data. The plugin stores keywords per post in post meta and may set transients for caching related posts. You can clear data by removing post meta or uninstalling the plugin. We recommend reviewing your site’s privacy policy to disclose use of Google Search Console data and the AISee service.

== Changelog ==

= 2.2 =
* Admin UX improvements and stability updates around keyword fetching and filtering.
* Related posts now cached for performance (15 days by default).
* Weekly cron and WP‑CLI support for batch taxonomy generation.

= 2.0 =
* Added connection with Google Search Console (GSC) via AISee service.
* Introduced AISee Terms/Tags taxonomies and inline keyword table.

= 1.0.1 =
* Initial release.

== Upgrade Notice ==
= 2.2 =
This update improves stability and adds caching for related posts. Review your settings and consider clearing old caches after upgrade.
