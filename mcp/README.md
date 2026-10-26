# Dischub MCP Server

A read-only [Model Context Protocol](https://modelcontextprotocol.io) server for the
[`ngoni/dischub-php`](https://packagist.org/packages/ngoni/dischub-php) package.

It lets AI assistants:

- search the package README, security policy, changelog and official Dischub API docs;
- read a full document by slug;
- validate redirect and non-redirect payment input using the same rules as the PHP package;
- read the status of an existing Dischub payment.

It **cannot** create orders, capture payments, issue refunds or move money.

## Tools

| Tool | Purpose |
| --- | --- |
| `dischub_search_docs` | Search documentation and return matching sections. |
| `dischub_get_doc` | Read a full document by slug, title or file name. |
| `dischub_validate_payment` | Validate a payload and return normalized values or field errors. |
| `dischub_get_payment_status` | Read the status of an existing payment. |

## Resources

- `dischub://docs` - JSON index of available documents.
- `dischub://docs/readme` - package README.
- `dischub://docs/security` - security policy.
- `dischub://docs/changelog` - changelog.
- `dischub://docs/official/...` - official Dischub API integration docs.

## Requirements

- Node.js 20 or newer.

## Run locally

```sh
npx -y dischub-mcp
```

The server uses stdio and is normally started by an MCP client, not from a terminal.

## Configuration

| Variable | Required | Description |
| --- | --- | --- |
| `DISCHUB_API_KEY` | For status lookups | Dischub API key. Sent as `X-Api-Key`. |
| `DISCHUB_RECIPIENT` | For status lookups | Merchant account email. `DISCHUB_RECIPIENT_EMAIL` is also accepted. |
| `DISCHUB_STATUS_URL` | No | Override the status endpoint. |
| `DISCHUB_TIMEOUT` | No | Request timeout in seconds. Default `30`, maximum `120`. |

Documentation search and payment validation work without any environment variables.

### Claude Desktop

Add this to `claude_desktop_config.json`:

```json
{
    "mcpServers": {
        "dischub": {
            "command": "npx",
            "args": ["-y", "dischub-mcp"],
            "env": {
                "DISCHUB_API_KEY": "your-api-key",
                "DISCHUB_RECIPIENT": "merchant@example.com"
            }
        }
    }
}
```

### opencode

Add this to `opencode.json`:

```json
{
    "$schema": "https://opencode.ai/config.json",
    "mcp": {
        "dischub": {
            "type": "local",
            "command": ["npx", "-y", "dischub-mcp"],
            "enabled": true,
            "environment": {
                "DISCHUB_API_KEY": "{env:DISCHUB_API_KEY}",
                "DISCHUB_RECIPIENT": "{env:DISCHUB_RECIPIENT}"
            }
        }
    }
}
```

Restart opencode after changing the config.

### Cursor and other clients

Use the same command and environment variables:

```text
command: npx
args: -y dischub-mcp
```

## Security

- The server is read-only by design.
- API keys are read from the MCP client environment and are never returned in tool output.
- The status lookup sends a POST request to the Dischub status endpoint because that is how
  the API works, but it does not create or modify a payment.
- Do not commit API keys to `opencode.json` or `claude_desktop_config.json`.

## Development

```sh
npm install
npm run build
npm test
npm run typecheck
```

The documentation under `docs/` is generated from the repository root:

```sh
npm run sync-docs
```

`npm run build` and `npm test` run the sync automatically. Use `npm pack --dry-run` to check
the published package contents.
