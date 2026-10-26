# Dischub MCP Server

A [Model Context Protocol](https://modelcontextprotocol.io) server for the
[`ngoni/dischub-php`](https://packagist.org/packages/ngoni/dischub-php) package.

It lets AI assistants:

- search the package README, security policy, changelog and official Dischub API docs;
- read a full document by slug;
- validate redirect and non-redirect payment input using the same rules as the PHP package;
- create and initiate Dischub payments from natural language;
- read the status of an existing Dischub payment.

It **cannot** capture payments, issue refunds, or move money directly.

## Tools

| Tool | Purpose |
| --- | --- |
| `dischub_search_docs` | Search documentation and return matching sections. |
| `dischub_get_doc` | Read a full document by slug, title or file name. |
| `dischub_validate_payment` | Validate a payload and return normalized values or field errors. |
| `dischub_create_payment` | Create and initiate a payment with amount and phone number. |
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

Set these environment variables in a `.env` file (create it in your project root):

```env
DISCHUB_API_KEY=your-api-key
DISCHUB_RECIPIENT=merchant@example.com
```

| Variable | Required | Description |
| --- | --- | --- |
| `DISCHUB_API_KEY` | For status lookups | Dischub API key. Sent as `X-Api-Key`. |
| `DISCHUB_RECIPIENT` | For status lookups | Merchant account email. `DISCHUB_RECIPIENT_EMAIL` is also accepted. |
| `DISCHUB_STATUS_URL` | No | Override the status endpoint. |
| `DISCHUB_TIMEOUT` | No | Request timeout in seconds. Default `30`, maximum `120`. |

**Important:** Add `.env` to `.gitignore` to avoid committing secrets:

```
echo ".env" >> .gitignore
```

Documentation search and payment validation work without any environment variables.

### Claude Code CLI

Install and configure with one command:

```sh
claude mcp add dischub-mcp -- npx dischub-mcp
```

The server reads `DISCHUB_API_KEY` and `DISCHUB_RECIPIENT` from your `.env` file automatically.

### Claude Desktop

Add this to `claude_desktop_config.json`:

```json
{
    "mcpServers": {
        "dischub": {
            "command": "npx",
            "args": ["-y", "dischub-mcp"]
        }
    }
}
```

Create a `.env` file in your home directory or project root with your credentials.

### opencode

Add this to `opencode.json`:

```json
{
    "$schema": "https://opencode.ai/config.json",
    "mcp": {
        "dischub": {
            "type": "local",
            "command": ["npx", "-y", "dischub-mcp"],
            "enabled": true
        }
    }
}
```

Create a `.env` file in your project root with:

```env
DISCHUB_API_KEY=your-api-key
DISCHUB_RECIPIENT=merchant@example.com
```

Restart opencode after creating the `.env` file.

### Cursor and other clients

Use the same command:

```text
command: npx
args: -y dischub-mcp
```

Create a `.env` file in your project root with your credentials.

## Security

- The server is read-only by design.
- API keys are read from the MCP client environment and are never returned in tool output.
- The status lookup sends a POST request to the Dischub status endpoint because that is how
  the API works, but it does not create or modify a payment.
- Do not commit API keys to `opencode.json` or `claude_desktop_config.json`.

## Testing Payments

### Quick Start (Fully Automated)

```sh
./mcp/scripts/setup-payment-test.sh
```

This script automatically:
- ✅ Downloads ngrok if not installed (supports Linux, macOS, Windows)
- ✅ Detects your OS and CPU architecture
- ✅ Creates sample redirect and callback pages
- ✅ Starts a local HTTP server
- ✅ Exposes them via ngrok public tunnel
- ✅ Displays ready-to-use payment URLs

**Example output:**
```
Redirect URL: https://abc123.ngrok.io/redirect.html
Callback URL: https://abc123.ngrok.io/callback.html
```

Then create a payment in Claude Code using those URLs.

### Manual Setup (If Preferred)

If you already have ngrok installed:

```sh
./mcp/scripts/test-payment.sh
```

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

