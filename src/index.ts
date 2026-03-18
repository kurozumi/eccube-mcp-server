#!/usr/bin/env node

import { runServer } from './server.js';

const baseUrl = process.env.ECCUBE_BASE_URL;
const apiKey = process.env.ECCUBE_API_KEY;

if (!baseUrl) {
  console.error('Error: ECCUBE_BASE_URL environment variable is required');
  process.exit(1);
}

if (!apiKey) {
  console.error('Error: ECCUBE_API_KEY environment variable is required');
  process.exit(1);
}

runServer({
  baseUrl,
  apiKey,
}).catch((error) => {
  console.error('Server error:', error);
  process.exit(1);
});
