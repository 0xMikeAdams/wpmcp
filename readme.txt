=== WPMCP - WordPress Model Context Protocol ===
Contributors: 0xmikeadams
Tags: api, mcp, ai, assistant, content, rest-api, json-rpc
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html

Expose WordPress content through the Model Context Protocol (MCP) for AI assistants and compatible tools.

== Description ==

WPMCP is a WordPress plugin that exposes your WordPress content through the Model Context Protocol (MCP), enabling AI assistants and other MCP-compatible tools to access and interact with your WordPress posts, pages, and custom post types in a structured, secure manner.

**Key Features:**

* **MCP Compliance** - Full implementation of Model Context Protocol specifications with JSON-RPC 2.0
* **Secure Access** - API key-based authentication with configurable rate limiting
* **Content Control** - Choose which post types are accessible through the API
* **Search Functionality** - Full-text search across WordPress content with relevance scoring
* **Admin Interface** - Easy-to-use WordPress admin panel for configuration
* **Security Logging** - Comprehensive request logging and monitoring
* **Performance Optimized** - Efficient content retrieval with pagination support

**Available MCP Tools:**

* `get_posts` - Retrieve WordPress posts with filtering options
* `get_post` - Get single post by ID or slug
* `get_pages` - Retrieve pages with hierarchy support
* `get_page` - Get single page by ID or slug
* `get_post_types` - List available post types and their metadata
* `search_content` - Full-text search across content with relevance scoring

**Perfect for:**

* AI assistants that need access to your WordPress content
* Content analysis and processing tools
* Automated content workflows
* Integration with MCP-compatible applications
* Developers building AI-powered WordPress solutions

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpmcp` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Settings > WPMCP screen to configure the plugin.
4. Generate API keys for your MCP clients.
5. Configure which post types should be accessible through the API.

== Frequently Asked Questions ==

= What is the Model Context Protocol (MCP)? =

The Model Context Protocol (MCP) is a standardized protocol that enables AI assistants and other tools to access and interact with external data sources in a structured way. It uses JSON-RPC 2.0 for communication.

= How do I generate an API key? =

1. Go to Settings > WPMCP in your WordPress admin
2. In the API Keys section, enter a name for your API key
3. Click "Generate API Key"
4. Copy the generated API key (you'll only see it once)
5. Use this API key in your MCP client configuration

= What is the API endpoint URL? =

The plugin creates a REST API endpoint at: `https://yoursite.com/wp-json/wpmcp/v1/mcp`

= How do I make requests to the API? =

Send POST requests to the endpoint with:
- Content-Type: application/json
- X-API-Key header with your API key
- JSON-RPC 2.0 formatted request body

Example:
```json
{
  "jsonrpc": "2.0",
  "method": "get_posts",
  "params": {
    "limit": 5,
    "post_type": "post"
  },
  "id": 1
}
```

= Can I control which content is accessible? =

Yes! In the plugin settings, you can:
- Choose which post types are accessible
- Set rate limits for API requests
- Enable/disable security logging
- Configure debug mode for troubleshooting

= Is my content secure? =

Absolutely. The plugin includes:
- API key authentication for all requests
- Rate limiting to prevent abuse
- Only published content is accessible
- WordPress user permissions are respected
- Comprehensive security logging
- No write access - read-only API

= What if I get a "No route found" error? =

This usually means:
1. The plugin isn't activated
2. You need to flush permalinks (Settings > Permalinks > Save)
3. You're making a GET request instead of POST
4. The API key is missing or invalid

= Can I use this with custom post types? =

Yes! The plugin supports all public custom post types. You can enable/disable specific post types in the plugin settings.

== Screenshots ==

1. Plugin settings page with API configuration options
2. API key management interface
3. Usage statistics and monitoring dashboard
4. Content access controls and post type selection

== Changelog ==

= 1.0.0 =
* Initial release
* MCP protocol implementation with JSON-RPC 2.0
* API key authentication system
* Content access controls for posts, pages, and custom post types
* Full-text search functionality with relevance scoring
* WordPress admin interface for configuration
* Security logging and request monitoring
* Rate limiting and abuse prevention
* Support for WordPress 5.0+ and PHP 7.4+

== Upgrade Notice ==

= 1.0.0 =
Initial release of WPMCP plugin. Install to enable MCP access to your WordPress content.

== Technical Details ==

**System Requirements:**
* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

**API Endpoint:**
* URL: `/wp-json/wpmcp/v1/mcp`
* Method: POST
* Authentication: X-API-Key header
* Format: JSON-RPC 2.0

**Database Tables:**
The plugin creates two custom tables:
* `wp_wpmcp_api_keys` - Stores API keys and metadata
* `wp_wpmcp_request_logs` - Logs API requests for monitoring

**Security Features:**
* API key-based authentication
* Configurable rate limiting (default: 100 requests/hour)
* Request logging and monitoring
* IP-based tracking
* Only published content access
* WordPress capability respect

== Support ==

For support, documentation, and updates, visit the plugin homepage or contact the development team.

== Privacy Policy ==

This plugin logs API requests for security and monitoring purposes. Logged data includes:
* API key used (not the actual key value)
* Request timestamp and IP address
* User agent and request details
* Response codes and timing

No personal content is logged. All logging can be disabled in plugin settings.

== Developer Information ==

**GitHub Repository:** Available for contributions and issue reporting
**Documentation:** Complete API documentation available
**Hooks and Filters:** Developer hooks available for customization
**Coding Standards:** Follows WordPress coding standards
**Testing:** Comprehensive test suite included