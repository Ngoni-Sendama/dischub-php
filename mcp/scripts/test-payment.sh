#!/bin/bash

# Dischub Payment Testing Script
# Created by Ngonidzashe Sendama +263783883412

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMP_DIR="/tmp/dischub-payment-test-$$"
PORT=3000

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Dischub Payment Testing Setup ===${NC}"

# Check if ngrok is installed
if ! command -v ngrok &> /dev/null; then
    echo -e "${RED}❌ ngrok is not installed${NC}"
    echo "Install ngrok from: https://ngrok.com/download"
    exit 1
fi

# Create temporary directory
mkdir -p "$TEMP_DIR"
cp "$SCRIPT_DIR/sample-redirect.html" "$TEMP_DIR/redirect"
cp "$SCRIPT_DIR/sample-callback.html" "$TEMP_DIR/callback"

echo -e "${GREEN}✓ Sample pages created${NC}"

# Start a simple HTTP server
cd "$TEMP_DIR"
echo -e "${BLUE}Starting HTTP server on port $PORT...${NC}"

# Use Python if available, otherwise Node
if command -v python3 &> /dev/null; then
    python3 -m http.server $PORT > /dev/null 2>&1 &
elif command -v python &> /dev/null; then
    python -m SimpleHTTPServer $PORT > /dev/null 2>&1 &
elif command -v node &> /dev/null; then
    npx http-server -p $PORT > /dev/null 2>&1 &
else
    echo -e "${RED}❌ No HTTP server available (Python or Node required)${NC}"
    rm -rf "$TEMP_DIR"
    exit 1
fi

SERVER_PID=$!
echo -e "${GREEN}✓ HTTP server running (PID: $SERVER_PID)${NC}"

# Start ngrok tunnel
echo -e "${BLUE}Starting ngrok tunnel...${NC}"
sleep 2

# Start ngrok in the background and capture output
NGROK_OUTPUT=$(mktemp)
ngrok http $PORT --log=stdout > "$NGROK_OUTPUT" 2>&1 &
NGROK_PID=$!
echo -e "${GREEN}✓ ngrok starting (PID: $NGROK_PID)${NC}"

# Wait for ngrok to start and extract the public URL
sleep 3
PUBLIC_URL=$(grep -oP 'https://[a-zA-Z0-9-]+\.ngrok\.io' "$NGROK_OUTPUT" | head -1)

if [ -z "$PUBLIC_URL" ]; then
    echo -e "${RED}❌ Failed to get ngrok public URL${NC}"
    kill $SERVER_PID $NGROK_PID 2>/dev/null || true
    rm -rf "$TEMP_DIR"
    exit 1
fi

echo -e "${GREEN}✓ ngrok tunnel established: ${BLUE}$PUBLIC_URL${NC}"

# Display the URLs
echo -e "\n${BLUE}=== Testing URLs ===${NC}"
echo -e "${GREEN}Redirect URL:${NC}  $PUBLIC_URL/redirect"
echo -e "${GREEN}Callback URL:${NC}  $PUBLIC_URL/callback"

echo -e "\n${YELLOW}🔗 Use these URLs when creating payments!${NC}"
echo -e "${YELLOW}The tunnel will stay active. Press Ctrl+C to stop.${NC}\n"

# Function to cleanup on exit
cleanup() {
    echo -e "\n${BLUE}Cleaning up...${NC}"
    kill $SERVER_PID 2>/dev/null || true
    kill $NGROK_PID 2>/dev/null || true
    rm -rf "$TEMP_DIR"
    echo -e "${GREEN}✓ Cleaned up${NC}"
}

# Set trap to cleanup on script exit
trap cleanup EXIT

# Keep the script running
wait
