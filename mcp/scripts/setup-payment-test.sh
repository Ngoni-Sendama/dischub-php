#!/bin/bash

# Dischub Payment Testing - Full Automation
# Created by Ngonidzashe Sendama +263783883412
# Auto-downloads ngrok and starts testing environment

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMP_DIR="/tmp/dischub-payment-test-$$"
PORT=3000
NGROK_VERSION="3.3.5"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

print_header() {
    echo -e "\n${BLUE}╔════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║   Dischub Payment Testing - Auto Setup   ║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════╝${NC}\n"
}

print_step() {
    echo -e "${CYAN}→${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Detect OS and architecture
detect_system() {
    OS=$(uname -s)
    ARCH=$(uname -m)

    case "$OS" in
        Linux*)
            OS_TYPE="linux"
            case "$ARCH" in
                x86_64) ARCH_TYPE="amd64" ;;
                aarch64) ARCH_TYPE="arm64" ;;
                *) ARCH_TYPE="amd64" ;;
            esac
            ;;
        Darwin*)
            OS_TYPE="darwin"
            case "$ARCH" in
                arm64) ARCH_TYPE="arm64" ;;
                *) ARCH_TYPE="amd64" ;;
            esac
            ;;
        MINGW*|MSYS*|CYGWIN*)
            OS_TYPE="windows"
            ARCH_TYPE="amd64"
            ;;
        *)
            OS_TYPE="linux"
            ARCH_TYPE="amd64"
            ;;
    esac

    print_success "Detected: $OS ($ARCH)"
}

# Download and setup ngrok
setup_ngrok() {
    print_step "Setting up ngrok..."

    # Check if ngrok is already installed globally
    if command -v ngrok &> /dev/null; then
        NGROK_CMD="ngrok"
        print_success "ngrok already installed"
        return 0
    fi

    # Create ngrok directory
    NGROK_DIR="$HOME/.dischub-ngrok"
    mkdir -p "$NGROK_DIR"

    # Check if we already downloaded ngrok
    if [ -f "$NGROK_DIR/ngrok" ] || [ -f "$NGROK_DIR/ngrok.exe" ]; then
        print_success "ngrok found in $NGROK_DIR"
        NGROK_CMD="$NGROK_DIR/ngrok"
        return 0
    fi

    # Download ngrok
    print_step "Downloading ngrok $NGROK_VERSION..."

    case "$OS_TYPE" in
        linux)
            DOWNLOAD_URL="https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v${NGROK_VERSION}-linux-${ARCH_TYPE}.tgz"
            ;;
        darwin)
            DOWNLOAD_URL="https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v${NGROK_VERSION}-darwin-${ARCH_TYPE}.zip"
            ;;
        windows)
            DOWNLOAD_URL="https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v${NGROK_VERSION}-windows-${ARCH_TYPE}.zip"
            ;;
        *)
            print_error "Unsupported OS: $OS_TYPE"
            return 1
            ;;
    esac

    # Download
    cd "$NGROK_DIR"

    if command -v curl &> /dev/null; then
        curl -sL "$DOWNLOAD_URL" -o ngrok.tar.gz
    elif command -v wget &> /dev/null; then
        wget -q "$DOWNLOAD_URL" -O ngrok.tar.gz
    else
        print_error "curl or wget required to download ngrok"
        return 1
    fi

    # Extract
    if [[ "$DOWNLOAD_URL" == *.zip ]]; then
        unzip -q ngrok.tar.gz
        rm ngrok.tar.gz
    else
        tar -xzf ngrok.tar.gz
        rm ngrok.tar.gz
    fi

    # Make executable
    chmod +x "$NGROK_DIR/ngrok"

    NGROK_CMD="$NGROK_DIR/ngrok"
    print_success "ngrok downloaded and ready"
}

# Start HTTP server
start_http_server() {
    print_step "Starting HTTP server on port $PORT..."

    mkdir -p "$TEMP_DIR"
    cp "$SCRIPT_DIR/sample-redirect.html" "$TEMP_DIR/redirect.html"
    cp "$SCRIPT_DIR/sample-callback.html" "$TEMP_DIR/callback.html"

    cd "$TEMP_DIR"

    # Create a simple router
    cat > index.html << 'EOF'
<!DOCTYPE html>
<html>
<head><title>Dischub Testing</title></head>
<body style="font-family: sans-serif; padding: 40px; text-align: center;">
    <h1>Dischub Payment Testing Server</h1>
    <p><a href="/redirect.html">Redirect Page</a> | <a href="/callback.html">Callback Receiver</a></p>
    <p style="color: #666; margin-top: 40px; font-size: 12px;">
        Created by Ngonidzashe Sendama | Testing MCP Server
    </p>
</body>
</html>
EOF

    # Start server based on available tools
    if command -v python3 &> /dev/null; then
        python3 -m http.server $PORT > /dev/null 2>&1 &
    elif command -v python &> /dev/null; then
        python -m SimpleHTTPServer $PORT > /dev/null 2>&1 &
    elif command -v node &> /dev/null; then
        npx http-server -p $PORT -c-1 > /dev/null 2>&1 &
    else
        print_error "No HTTP server available (Python or Node required)"
        return 1
    fi

    HTTP_PID=$!
    sleep 1

    # Test if server is running
    if ! kill -0 $HTTP_PID 2>/dev/null; then
        print_error "Failed to start HTTP server"
        return 1
    fi

    print_success "HTTP server running (PID: $HTTP_PID)"
}

# Start ngrok tunnel
start_ngrok_tunnel() {
    print_step "Starting ngrok tunnel..."

    NGROK_LOG="$TEMP_DIR/ngrok.log"

    # Start ngrok
    $NGROK_CMD http $PORT --log=stdout > "$NGROK_LOG" 2>&1 &
    NGROK_PID=$!
    sleep 4

    # Extract public URL
    PUBLIC_URL=$(grep -oP 'https://[a-zA-Z0-9-]+\.ngrok\.io' "$NGROK_LOG" | head -1 || true)

    if [ -z "$PUBLIC_URL" ]; then
        print_error "Failed to get ngrok public URL"
        return 1
    fi

    print_success "ngrok tunnel established (PID: $NGROK_PID)"
}

# Create environment file for Claude Code
create_env_file() {
    ENV_FILE="$TEMP_DIR/.dischub-urls"
    cat > "$ENV_FILE" << EOF
DISCHUB_TEST_REDIRECT_URL=$PUBLIC_URL/redirect.html
DISCHUB_TEST_CALLBACK_URL=$PUBLIC_URL/callback.html
DISCHUB_TEST_NGROK_PID=$NGROK_PID
DISCHUB_TEST_HTTP_PID=$HTTP_PID
DISCHUB_TEST_TEMP_DIR=$TEMP_DIR
EOF

    print_success "Environment saved to: $ENV_FILE"
}

# Display URLs
display_urls() {
    echo -e "\n${GREEN}╔════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║        🎉 Testing Setup Complete!       ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════╝${NC}\n"

    echo -e "${YELLOW}📋 Use these URLs for payment testing:${NC}\n"
    echo -e "  ${CYAN}Redirect URL:${NC}"
    echo -e "  ${BLUE}$PUBLIC_URL/redirect.html${NC}\n"
    echo -e "  ${CYAN}Callback URL:${NC}"
    echo -e "  ${BLUE}$PUBLIC_URL/callback.html${NC}\n"

    echo -e "${YELLOW}💬 Example Claude Code command:${NC}"
    echo -e "  ${BLUE}Create a redirect payment for 50 ZWG to +263783883412${NC}"
    echo -e "  ${BLUE}redirect_url: $PUBLIC_URL/redirect.html${NC}"
    echo -e "  ${BLUE}callback_url: $PUBLIC_URL/callback.html${NC}\n"

    echo -e "${YELLOW}⚙️  Server Info:${NC}"
    echo -e "  HTTP Server:  PID $HTTP_PID"
    echo -e "  ngrok Tunnel: PID $NGROK_PID"
    echo -e "  Temp Dir:     $TEMP_DIR\n"

    echo -e "${YELLOW}📍 Visit:${NC} ${BLUE}$PUBLIC_URL${NC} ${YELLOW}(to see menu)${NC}\n"
    echo -e "${YELLOW}Press Ctrl+C to stop all services${NC}\n"
}

# Cleanup function
cleanup() {
    echo -e "\n${BLUE}Shutting down services...${NC}"

    # Kill ngrok
    if [ ! -z "$NGROK_PID" ]; then
        kill $NGROK_PID 2>/dev/null || true
        print_success "Stopped ngrok"
    fi

    # Kill HTTP server
    if [ ! -z "$HTTP_PID" ]; then
        kill $HTTP_PID 2>/dev/null || true
        print_success "Stopped HTTP server"
    fi

    # Cleanup temp directory
    if [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
        print_success "Cleaned up temporary files"
    fi

    echo -e "\n${GREEN}✓ Cleanup complete${NC}\n"
}

# Main execution
main() {
    print_header

    # Trap Ctrl+C
    trap cleanup EXIT INT TERM

    # Setup steps
    detect_system
    setup_ngrok || exit 1
    start_http_server || exit 1
    start_ngrok_tunnel || exit 1
    create_env_file
    display_urls

    # Keep script running
    wait
}

# Run main
main
