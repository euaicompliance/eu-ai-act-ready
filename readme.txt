=== EU AI Act Ready ===
Contributors: aicompliance
Tags: eu ai act, article 50, ai transparency, ai compliance, ai disclosure
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

EU AI Act compliance for WordPress. Disclose AI content and chatbots. Track readiness with built-in score, self-assessment wizard, and export reports.

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
* **Bulk Scanning Tools** - Scan multiple media items simultaneously from the admin dashboard
* **Manual Override Controls** - Mark or unmark content and media as AI-generated at any time
* **Customizable Disclosure Messages** - Configure wording, style, and placement of transparency notices; each disclosure level shows its own default message or a shared custom message
* **Lightweight & Performance-Friendly** - Detection runs asynchronously or on demand without slowing down your site

= Minimum Requirements =

* PHP 7.4 or greater (PHP 8.0 or greater recommended)
* MySQL 5.5.5 or greater, OR MariaDB 10.1 or greater
* WordPress 6.0 or greater

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/eu-ai-act-ready/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Navigate to **EU AI Act Ready --> Settings** to configure disclosure notices, detection options, and chatbot transparency.

== Frequently Asked Questions ==

= How does the plugin detect AI-generated media? =
The plugin flags potentially AI-generated images using multiple heuristic signals, including attachment metadata, filenames, EXIF information, and common technical patterns. Images can also be manually marked or unmarked at any time.

= Does the plugin automatically detect AI-generated text? =
No. AI-generated text disclosure must be applied manually by the site owner to ensure accuracy and editorial control. The plugin does not analyze or infer AI usage in text content.

= Can I customize the disclosure messages? =
Yes. You can fully customize the wording, placement, and style of disclosure notices from **EU AI Act Ready --> Settings**.

= Does this plugin slow down my site? =
No. Detection processes run asynchronously or on demand. Front-end output is lightweight and loads only when disclosures are enabled.

= Which AI tools and chatbot platforms are supported? =
EU AI Act Ready supports transparency disclosures for AI-generated content created using tools such as ChatGPT, GPT-4, DALL-E, Claude, Google Gemini, Midjourney, Stable Diffusion, GitHub Copilot, and similar AI platforms.

For chatbots, the plugin supports disclosure notices for platforms including Formilla, Intercom, Drift, Tidio, Tawk.to, Zendesk Chat, LiveChat, Crisp, Freshchat, as well as custom or self-hosted chatbot implementations. Chatbot disclosures can be enabled per platform from the plugin settings.

= Is this plugin GDPR compliant? =
Yes. The plugin does not collect, store, or transmit personal data and does not process user data for AI detection or AI transparency features. All processing happens locally on your server, and no data is sent to third-party services.

= Does it work with Gutenberg, Elementor, and the Classic Editor? =
Yes. EU AI Act Ready works with the Gutenberg block editor, Elementor, and the Classic Editor. The AI Content Disclosure meta box appears in the editor and lets you choose from four disclosure levels. You can also set the disclosure level from the Posts list using Quick Edit, without opening the post editor.

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

= After updating to version 1.0.2 on a German-language site, the plugin seems to have disappeared. How do I fix this? =
Version 1.0.2 shipped with a translation bug: the plugin's own name "EU AI Act Ready" was incorrectly translated to "EU-KI-Act-konform" in the German language file. On sites running WordPress in German (de_DE), this caused the plugin to appear under the translated name in the Plugins list, and - if you replaced the plugin files manually - WordPress may have auto-deactivated it while the directory was momentarily empty.

**Your settings and data are safe.** No database content is deleted when a plugin is deactivated.

To recover:

1. Go to **Plugins --> Installed Plugins** in your WordPress admin.
2. Look for either **"EU AI Act Ready"** or **"EU-KI-Act-konform"** in the list (depending on your WordPress version, it may appear under either name).
3. Click **Activate**.

That is all. All previously saved settings, AI content markers, and media scan results are restored immediately.

The translation bug is fixed in version 1.0.3. After updating to the fixed release the plugin name will always display as "EU AI Act Ready" regardless of the site language.

== Changelog ==

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
