# WPMCP - WordPress Model Context Protocol Plugin

A WordPress plugin that exposes WordPress content through the Model Context Protocol (MCP), enabling AI assistants and other MCP-compatible tools to access and interact with WordPress posts, pages, and custom post types in a structured, secure manner.

## Features

- **MCP Compliance**: Full implementation of Model Context Protocol specifications with JSON-RPC 2.0
- **Content Access**: Secure read access to WordPress posts, pages, and custom post types
- **API Key Authentication**: Secure API key-based authentication system
- **Rate Limiting**: Configurable rate limiting to prevent abuse
- **Search Functionality**: Full-text search across WordPress content with relevance scoring
- **Admin Interface**: Easy-to-use WordPress admin panel for configuration
- **Security Logging**: Comprehensive request logging and security monitoring

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Installation

1. Download the plugin files
2. Upload to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the WordPress admin panel
4. Go to Settings > WPMCP to configure the plugin

## Configuration

### API Keys

1. Navigate to Settings > WPMCP in your WordPress admin
2. In the API Keys section, enter a name for your API key
3. Click "Generate API Key"
4. Copy the generated API key (you'll only see it once)
5. Use this API key in your MCP client configuration

### Content Access

Configure which post types are accessible through the API:

- Posts (default: enabled)
- Pages (default: enabled)
- Custom post types (configurable)

### Security Settings

- **Rate Limit**: Set maximum requests per API key per hour (default: 100)
- **Security Logging**: Enable/disable request logging (default: enabled)
- **Debug Mode**: Enable detailed logging for troubleshooting (default: disabled)

## MCP Endpoint

The plugin creates a REST API endpoint at:
```
https://yoursite.com/wp-json/wpmcp/v1/mcp
```

## Available MCP Tools

### get_posts
Retrieve WordPress posts with filtering options.

**Parameters:**
- `post_type` (string): Post type to retrieve (default: 'post')
- `limit` (int): Number of posts to retrieve (max: 100, default: 10)
- `offset` (int): Offset for pagination (default: 0)
- `filters` (object): Additional filters (date, author, category, etc.)

### get_post
Retrieve a single WordPress post by ID or slug.

**Parameters:**
- `post_id` (int): Post ID
- `slug` (string): Post slug (alternative to post_id)

### get_pages
Retrieve WordPress pages with hierarchy support.

**Parameters:**
- `limit` (int): Number of pages to retrieve (max: 100, default: 10)
- `offset` (int): Offset for pagination (default: 0)
- `parent_id` (int): Filter by parent page ID
- `include_hierarchy` (bool): Include child pages in results

### get_page
Retrieve a single WordPress page by ID or slug.

**Parameters:**
- `page_id` (int): Page ID
- `slug` (string): Page slug (alternative to page_id)

### get_post_types
Retrieve available post types and their metadata.

**Parameters:**
- `include_builtin` (bool): Include built-in post types (default: false)

### search_content
Search across WordPress content with relevance scoring.

**Parameters:**
- `query` (string): Search query (required)
- `post_types` (array): Post types to search (default: all allowed types)
- `limit` (int): Number of results (max: 50, default: 10)
- `offset` (int): Offset for pagination (default: 0)
- `filters` (object): Additional filters

## Example MCP Client Configuration

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "curl",
      "args": [
        "-X", "POST",
        "-H", "Content-Type: application/json",
        "-H", "X-API-Key: your-api-key-here",
        "https://yoursite.com/wp-json/wpmcp/v1/mcp"
      ]
    }
  }
}
```

## Example Request

```json
{
  "jsonrpc": "2.0",
  "method": "get_posts",
  "params": {
    "post_type": "post",
    "limit": 5,
    "filters": {
      "category": "technology",
      "date_after": "2024-01-01"
    }
  },
  "id": 1
}
```

## Security

- All requests require valid API key authentication
- Rate limiting prevents abuse
- Only published content is accessible
- WordPress user permissions are respected
- Security events are logged for monitoring

## Development

### Running Tests

```bash
composer install
composer test
```

### Code Standards

```bash
composer phpcs  # Check coding standards
composer phpcbf # Fix coding standards
```

### Database Tables

The plugin creates two custom tables:

- `wp_wpmcp_api_keys`: Stores API keys and their metadata
- `wp_wpmcp_request_logs`: Logs API requests for security monitoring

## Troubleshooting

### Common Issues

1. **404 Error on Endpoint**: Flush permalink structure (Settings > Permalinks > Save)
2. **Authentication Failed**: Verify API key is active and correctly formatted
3. **Rate Limit Exceeded**: Check rate limit settings or wait for limit reset
4. **No Content Returned**: Verify post types are enabled in plugin settings

### Debug Mode

Enable debug mode in plugin settings to get detailed logging information. Check WordPress debug logs for detailed error messages.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- MCP protocol implementation
- API key authentication
- Content access controls
- Search functionality
- Admin interface
- Security logging