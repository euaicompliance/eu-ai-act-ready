=== EU AI Act Ready ===
Contributors: aicompliance
Tags: eu ai act, article 50, ai transparency, ai compliance, ai disclosure
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

EU AI Act compliance for WordPress. Disclose AI content, images and chatbots. Track readiness with built-in score, self-assessment wizard, and export reports.

== Description ==

EU AI Act Ready is a practical WordPress compliance plugin that helps site owners meet the transparency and disclosure obligations introduced by the EU AI Act. It provides everything you need to disclose AI-generated content, AI-powered chatbots, and AI systems to visitors - and to track and document your compliance progress through a readiness score, self-assessment wizard, and exportable compliance report.

The EU AI Act is now in force. Article 50 requires disclosure when users interact with AI systems, AI-generated content, or AI-manipulated media. Article 4 requires deployers of AI systems to take steps to ensure that staff have sufficient AI literacy. EU AI Act Ready addresses both articles with practical, locally-running WordPress tools. No content or personal data is sent to external services.

EU AI Act Ready is a technical transparency tool. It does not provide legal advice or guarantee regulatory compliance.

= EU AI Act Articles Covered =

**Article 50 - Transparency obligations**

Article 50 requires clear disclosure when users interact with AI systems or AI-generated content. Specifically:

* Chatbots and virtual assistants must inform users they are interacting with an AI, not a human.
* AI-generated or AI-manipulated content - including text, images, audio, and video - must be labelled.
* Content that could be mistaken for authentic human-created material requires clear disclosure.

EU AI Act Ready supports these requirements through configurable visitor notices for AI content, AI-powered chatbots, and AI systems detected on your site.

**Article 4 - AI Literacy obligations**

Article 4 requires deployers of AI systems to take reasonable steps to ensure that their staff have sufficient AI literacy - understanding what AI tools are in use, their capabilities, their limitations, and how to raise concerns. The AI Literacy checklist in EU AI Act Ready lets you track whether your organisation has addressed these obligations.

Final responsibility for assessing and meeting legal obligations remains with the site owner or their legal counsel.

= Who This Plugin Is For =

* **Bloggers and content creators** who publish AI-generated articles, images, or media and need to disclose this clearly to readers
* **Businesses using AI chatbots** for customer support, lead generation, or virtual assistance
* **E-commerce and SaaS sites** integrating AI personalisation, recommendation tools, or AI-generated product descriptions
* **Agencies and developers** building compliant WordPress sites for EU-based clients who need documented transparency measures
* **News sites and publishers** disclosing the use of AI tools in content research or production
* **Any WordPress site owner** who wants to document AI transparency for internal audit, stakeholder review, or legal assessment

= Key Features =

* **Four Disclosure Levels** - Choose from No AI used, AI-assisted, AI-generated, or AI-generated & human-reviewed - each level shows a distinct, accurate notice to visitors
* **Frontend Transparency Notices** - Automatically display clear visitor notices when content is marked as AI-generated, in four configurable styles: Banner, Inline, Badge, or Modal
* **Quick Edit & Bulk Support** - Set the AI disclosure level directly from the Posts list via Quick Edit or bulk actions, without opening the post editor
* **Shortcode Support** - Place an AI disclosure notice anywhere using [eu_ai_disclosure]; supports type (content/image/chatbot), style, and message attributes with automatic fallback to saved settings
* **RSS & Feed Disclosure** - Optionally append a plain-text AI disclosure to RSS feed items and prefix titles with [AI Content] for AI-marked posts, keeping feed readers informed
* **Chatbot Transparency** - Adds disclosure notices for 15+ AI-powered chatbot platforms including Formilla, Intercom, Drift, Tidio, Tawk.to, Zendesk Chat, LiveChat, Crisp, Freshchat, Smartsupp, HubSpot Chat, Chaport, Userlike, Olark, and Chatra, in five styles: Banner, Badge, Inline, Modal, or Tooltip; includes Auto-detect mode that automatically identifies the active platform
* **Compliance Readiness Score** - Dashboard displays a 0-100 readiness score across six key obligations with a traffic-light indicator and a checklist of unmet items so site owners know exactly what to address
* **AI Systems Registry** - Detects installed WordPress plugins that use AI and matches them against a remote registry of 140+ AI tools; also uses "Possibly AI" heuristic scoring to flag candidate plugins not yet in the registry, and probes your site's frontend for known AI tool JavaScript signatures; shows all detected AI systems with category, EU AI Act article, and per-tool show/hide toggle for the frontend disclosure notice
* **WordPress Dashboard Widget** - A compact readiness overview on the main WordPress Dashboard showing your current readiness score, detected AI system count, AI content count, and quick links to plugin pages
* **Compliance Self-Assessment** - Step-by-step wizard covering six EU AI Act use cases (chatbots, AI content, AI images, personalisation, translation, synthetic media); identifies applicable obligations and recommends next actions
* **AI Literacy Checklist (Article 4)** - Track whether staff awareness, policy documentation, and training obligations have been addressed; completion counts toward the readiness score
* **Export Compliance Report** - Generate a print-friendly report summarising score, AI systems, assessment answers, and literacy status; suitable for internal audit or stakeholder review
* **EU AI Act Timeline** - Dashboard section showing enforcement dates with countdowns so you always know what is coming and when
* **AI Content Admin Page** - Dedicated admin page listing all AI-marked content items (posts, pages, and custom post types) with their disclosure level, with one-click unmark and bulk actions
* **Media & Image Analysis** - Flags potentially AI-generated images using heuristic metadata signals and filename patterns
* **AI Image Labels** - Visitor-facing labels on AI-flagged images with configurable tooltip and size; works with Gutenberg, Elementor, Classic Editor, and Bricks Builder, plus an opt-in filter any theme can use to label images it renders itself, including CSS background images in hero and header areas
* **Bulk Scanning Tools** - Scan multiple media items simultaneously from the admin dashboard
* **Manual Override Controls** - Mark or unmark content and media as AI-generated at any time
* **Customizable Disclosure Messages** - Configure wording, style, and placement of transparency notices - show the notice above the content, below it, or in both places; each disclosure level shows its own default message or a shared custom message
* **Lightweight & Performance-Friendly** - Detection runs asynchronously or on demand without slowing down your site

= Minimum Requirements =

* PHP 7.4 or greater (PHP 8.0 or greater recommended)
* MySQL 5.5.5 or greater, OR MariaDB 10.1 or greater
* WordPress 6.0 or greater

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/eu-ai-act-ready/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Navigate to **EU AI Act Ready --> Settings** to configure disclosure notices, detection options, and chatbot transparency.

== Screenshots ==

1. Dashboard showing the Compliance Readiness Score, obligation checklist, and content statistics at a glance.
2. Transparency Status indicators and EU AI Act Enforcement Timeline with upcoming enforcement dates.
3. Self-Assessment results page listing applicable EU AI Act obligations and recommended configuration actions.
4. Six-step Self-Assessment wizard that identifies which EU AI Act obligations apply to your site.
5. AI Systems page showing the frontend disclosure notice settings and automatically detected AI tools.
6. Manual AI tool declaration panel with heuristic "Possibly AI" flagging for unrecognised plugins.
7. Article 4 AI Literacy checklist tracking staff awareness, policy documentation, and training obligations.
8. RSS & Feed Disclosure settings - append AI disclosure notes and title prefixes to RSS feed items.
9. Shortcode reference panel showing how to place AI disclosure notices anywhere using [eu_ai_disclosure].
10. AI Content Disclosure meta box in the post editor - choose from four disclosure levels per post.
11. Content Transparency settings - enable notices, choose banner/inline/badge/modal style, and set a custom message.
12. Media library AI detection - mark images as AI-generated and review heuristic metadata signals.
13. Frontend transparency notices in action: content banner, image label, and floating AI systems badge.

== Frequently Asked Questions ==

= Is EU AI Act Ready free? =
Yes. Everything described on this page is included: the readiness score, self-assessment wizard, AI Systems registry, AI content and image disclosure, chatbot transparency notices, RSS disclosure, the Article 4 AI literacy checklist, and the exportable compliance report. There is no trial period, no account to create, no license key to enter, and no feature that stops working after a set number of posts or images. The plugin is licensed under GPLv2 or later.

The AI tools registry is fetched from eu-ai-act-ready.com at no cost and requires no registration or API key. If it cannot be reached, the plugin keeps using the tool list it last downloaded, and you can declare AI tools manually at any time.

= How does the plugin detect AI-generated media? =
The plugin flags potentially AI-generated images using multiple heuristic signals, including attachment metadata, filenames, EXIF information, and common technical patterns. Images can also be manually marked or unmarked at any time.

= Does the plugin automatically detect AI-generated text? =
No. AI-generated text disclosure must be applied manually by the site owner to ensure accuracy and editorial control. The plugin does not analyze or infer AI usage in text content.

= Can I customize the disclosure messages? =
Yes. You can fully customize the wording, placement, and style of disclosure notices from **EU AI Act Ready --> Settings**. Under **Content Transparency** you can also choose whether the notice appears above the content, below it, or in both places.

= My theme renders images as CSS backgrounds and they are not labelled. Can I fix that? =
Yes. Automatic labelling needs an `<img>` tag to attach the label to, so an image rendered as a CSS background - a common pattern for hero and header areas - is never seen. Pass the markup your template renders through the `euaiactready_label_media` filter and both background images and plain `<img>` tags in it will be labelled:

`echo apply_filters( 'euaiactready_label_media', $header_html );`

No `function_exists()` guard is needed: if the plugin is inactive the filter is simply unregistered and the markup is returned unchanged. Where the badge sits on a background image is controlled by **Settings --> Media/Image Labels --> Badge Placement (Background Images)**.

= Does this plugin slow down my site? =
No. Detection processes run asynchronously or on demand. Front-end output is lightweight and loads only when disclosures are enabled.

= Which AI tools and chatbot platforms are supported? =
EU AI Act Ready supports transparency disclosures for AI-generated content created using tools such as ChatGPT, GPT-4, DALL-E, Claude, Google Gemini, Midjourney, Stable Diffusion, GitHub Copilot, and similar AI platforms.

For chatbots, the plugin supports disclosure notices for platforms including Formilla, Intercom, Drift, Tidio, Tawk.to, Zendesk Chat, LiveChat, Crisp, Freshchat, as well as custom or self-hosted chatbot implementations. Chatbot disclosures can be enabled per platform from the plugin settings.

= Is this plugin GDPR compliant? =
Yes. The plugin does not collect, store, or transmit personal data and does not process user data for AI detection or AI transparency features. All processing happens locally on your server, and no data is sent to third-party services.

= Does it work with Gutenberg, Elementor, and the Classic Editor? =
Yes. EU AI Act Ready works with the Gutenberg block editor, Elementor, and the Classic Editor. The AI Content Disclosure meta box appears in the editor and lets you choose from four disclosure levels. You can also set the disclosure level from the Posts list using Quick Edit, without opening the post editor. AI image labels are also supported in Bricks Builder output.

= How do I bulk scan existing images? =
Go to **EU AI Act Ready --> Dashboard** and use the bulk scanning tools to process media library items in batches.

= Can I use this with custom post types? =
Yes. From **Settings --> Content Transparency --> Content Types**, you can enable the AI disclosure meta box, list column, and bulk actions for any public post type registered by installed themes and plugins. Posts and pages are enabled by default; all other public post types can be toggled on or off.

= Can I place an AI disclosure notice inside my content using a shortcode? =
Yes. Use the [eu_ai_disclosure] shortcode to insert a notice anywhere WordPress accepts shortcodes - posts, pages, widgets, or block templates. The shortcode supports optional type (content, image, chatbot), style (banner, inline, badge, modal), and message attributes. When attributes are omitted, the shortcode falls back to your saved Settings values. The shortcode renders regardless of whether the global transparency toggle is on or off.

= Does the plugin add AI disclosures to my RSS feed? =
Yes, optionally. From **EU AI Act Ready --> Settings --> RSS & Feeds** you can enable a plain-text "- AI Disclosure: …" line appended to RSS feed content and excerpts for posts marked as AI-generated. You can also enable a [AI Content] prefix on RSS feed titles. Both options are off by default. RSS disclosure works independently of the global transparency toggle.

= Can I manually mark content as AI-generated? =
Yes. You can manually mark or unmark any post, page, custom post type, or media item as AI-generated using editor controls or bulk actions.

= How does the Compliance Readiness Score work? =
The readiness score is a number from 0 to 100 calculated from six key obligations: AI content disclosures active, AI content items disclosed, AI images scanned, chatbot transparency configured, AI systems declared, and Article 4 AI literacy addressed. Each met obligation contributes equally to the score. The score and a checklist of unmet items are shown on the Dashboard and in the WordPress Dashboard widget.

= How does the Self-Assessment wizard work? =
The Self-Assessment wizard on the Assessment page walks you through six questions about how your site uses AI - covering chatbots, AI-generated content, AI images, personalisation, translation, and synthetic media. Based on your answers, the plugin identifies which EU AI Act obligations are likely applicable and recommends actions. Completing the assessment counts toward your Compliance Readiness Score.

= Can I export a compliance report? =
Yes. Click the Export Report button on the Dashboard. A new browser tab opens with a print-friendly report summarising your readiness score, detected AI systems, self-assessment answers, and AI literacy checklist status. You can print or save the report as a PDF for internal audit or stakeholder review. The report includes the site URL and the date it was generated.

= What is Article 4 AI Literacy and how does the checklist help? =
Article 4 of the EU AI Act requires deployers of AI systems to take reasonable steps to ensure their staff have sufficient AI literacy - meaning they understand what AI tools are in use, their capabilities, and how to raise concerns. The AI Literacy checklist on the AI Literacy page lets you track whether key obligations have been addressed (such as informing staff, documenting policies, and arranging training). Completing the checklist contributes to your Compliance Readiness Score.

= What happens if I deactivate the plugin? =
All plugin settings and AI content markers remain stored in the database. Reactivating the plugin restores all functionality. To remove all data, uninstall the plugin.


== Changelog ==

= 2.2.0 =
* Added: Notice Position setting - place the content transparency notice above the content, below it, or in both places (Settings --> Content Transparency). Existing sites keep the previous "above the content" behaviour. (props @archandha)
* Added: `euaiactready_notice_position` and `euaiactready_notice_html` filters so themes can override notice placement and markup without patching the plugin. (props @archandha)
* Added: `euaiactready_label_media` filter - any theme can now have AI images labelled in markup it renders itself, by passing the fragment through the filter. Handles both plain `<img>` tags and images rendered as CSS backgrounds in hero and header areas, which previously could only be labelled in Bricks Builder output. (props @archandha)
* Improved: The "Badge Placement (Background Images)" setting is now shown on every site, not only when Bricks Builder is active, so background labels can be repositioned anywhere they appear. (props @archandha)
* Added: German translations for the new Notice Position setting. (props @archandha)

= 2.1.1 =
* Fixed: Added Author URI

= 2.1.0 =
* Added: Extends AI transparency labels to a post's featured image, not just images inside the content.
* Added: Bricks Builder support - AI images are now labelled in Bricks Builder output (props @13robin37)
* Added: Configurable tooltip and size for AI image labels (props @13robin37)
* Fixed: AI detection status now reflects the confidence threshold instead of assuming any scan means AI (props @13robin37)
* Fixed: Content transparency banner uses role="note" instead of role="alert" for better accessibility (props @archandha)

= 2.0.3 =
* Security: Fixed missing return statement after failed nonce and permission checks in AJAX handler to prevent execution from continuing past a failed security verification.

= 2.0.2 =
* Added: Add new screenshots.

= 2.0.1 =
* Added: Add new screenshots.

= 2.0.0 =
* Added: Compliance Self-Assessment wizard (6 questions) to identify which EU AI Act obligations apply to your site; applicable articles and recommended actions shown on completion.
* Added: AI Literacy checklist (Article 4) - track whether staff are informed, policies are documented, and training is in place; completion is integrated into the readiness score.
* Added: Compliance Readiness Score (0-100) on the Dashboard with a traffic-light indicator (green/amber/red) and a checklist of unmet obligations; calculates across six key EU AI Act factors.
* Added: Export Report button on the Dashboard - opens a print-friendly compliance report in a new tab covering score, AI systems, assessment answers, and literacy status.
* Added: AI Systems Registry - detects installed WordPress plugins that use AI against a remote registry of 140+ AI tools; five frontend notice styles (Floating, Banner, Inline, Badge, Modal) with enable/disable toggle and protected registry access.
* Added: "Possibly AI" heuristic detection - scores installed plugin names and descriptions against AI keywords and flags candidate plugins not yet in the registry.
* Added: Server-side site fetch - scans your site's homepage and latest post for known AI tool JavaScript signatures; results cached for 12 hours.
* Added: Manual AI tool declarations - flag any installed plugin as AI-related even if it is not yet in the registry.
* Added: Watchtower auto-rescan - detected AI tools list refreshes automatically whenever a WordPress plugin is activated or deactivated.
* Added: Daily WP Cron task to refresh the remote AI tools registry and re-run detection automatically.
* Added: Chatbot auto-detect mode - probes all 15 supported platforms automatically without requiring manual platform selection.
* Added: Six new chatbot platform detections - Smartsupp, HubSpot Chat, Chaport, Userlike, Olark, and Chatra - bringing total supported platforms to 15.
* Added: EU AI Act Enforcement Timeline on the Dashboard showing key enforcement dates with countdowns for upcoming dates.
* Added: WordPress Dashboard widget showing readiness score, AI system count, AI content count, and quick navigation links.
* Added: [eu_ai_disclosure] shortcode to place AI disclosure notices anywhere WordPress accepts shortcodes; supports type (content/image/chatbot), style, and message attributes; works independently of the global transparency toggle.
* Added: RSS & Feed disclosure - optionally append a plain-text AI disclosure to RSS feed content and excerpts for AI-marked posts; works independently of the global transparency toggle (Settings --> RSS & Feeds).
* Added: RSS title prefix option - prepend [AI Content] to RSS feed titles for AI-marked posts.
* Improved: Admin navigation reorganised to reflect all new plugin areas: Dashboard, Assessment, AI Systems, AI Content, AI Images, AI Literacy, Settings.

= 1.1.0 =
* Added: Four AI disclosure levels - No AI used, AI-assisted, AI-generated, and AI-generated & human-reviewed - replacing the previous single checkbox.
* Added: Each disclosure level shows its own default notice text on the frontend; a shared custom message can override all levels from Settings.
* Added: Quick Edit support - set the AI disclosure level directly from the Posts list without opening the post editor.
* Added: Dedicated AI Content admin page listing all AI-marked posts with their disclosure level, one-click unmark, and bulk unmark action.
* Added: Four content notice display styles - Banner, Inline, Badge, and Modal.
* Added: Five chatbot transparency notice styles - Banner, Badge, Inline, Modal, and Tooltip.
* Added: Custom post type support - any public post type registered by a theme or plugin can now be enabled for AI content disclosure from Settings --> Content Transparency --> Content Types.
* Added: Posts and pages remain enabled by default and can be individually deactivated from the same settings section.
* Improved: Posts list filter includes all four disclosure levels for precise filtering.
* Improved: The AI Content management page now lists AI-marked items from all enabled post types, not only posts and pages.

= 1.0.4 =
* Compatibility: Tested up to WordPress 7.0

= 1.0.3 =
* Fixed: German translation incorrectly translated the plugin name, causing it to appear as "EU-KI-Act-konform" on German-language sites and preventing WordPress from recognising the previously active plugin after a manual update.  (props @archandha)

= 1.0.2 =
* Added: German (de_DE) translation (props @archandha)

= 1.0.1 =
* Fixed: stripos() error when processing array values in media EXIF data (props @archandha)

= 1.0.0 =
* Initial release
