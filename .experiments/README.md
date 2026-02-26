# Argon Experiments

## Quick Start
```sh
cd .experiments
composer install
php -S localhost:8001 -t .
```

Open `http://localhost:8001/index.html`.

## What This Does
- Runs a minimal Argon workflow over SSE.
- Streams `ExecutionEvent` payloads to the browser.
- The UI logs events live.

## Notes
- Change the `runId` by adding `?runId=your-id` to the SSE URL in the browser.
