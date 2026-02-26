const statusEl = document.getElementById("status");
const logEl = document.getElementById("log");
const graphEl = document.getElementById("graph");

const nodes = {};

function log(line) {
  logEl.textContent += line + "\n";
  logEl.scrollTop = logEl.scrollHeight;
}

// Quick-and-dirty placeholder: swap for your SSE endpoint later.
// Example: `/api/workflow-runs/${runId}/stream`
const url = "/server/index.php?runId=demo-run";
const graphUrl = "/server/graph.php";

renderGraph();

statusEl.textContent = `Status: connecting to ${url}`;

try {
  const es = new EventSource(url);

  es.addEventListener("open", () => {
    statusEl.textContent = `Status: connected (${url})`;
  });

  es.addEventListener("error", () => {
    statusEl.textContent = "Status: disconnected (retrying...)";
  });

  es.addEventListener("workflow", (event) => {
    const payload = JSON.parse(event.data);
    log(`[${payload.type}] ${payload.state} (run=${payload.runId})`);

    if (payload.type === "step.started") {
      setActive(payload.state);
    }

    if (payload.type === "step.failed") {
      setFailed(payload.state);
    }

    if (payload.type === "run.finished") {
      setDone(payload.state);
    }
  });
} catch (err) {
  statusEl.textContent = "Status: EventSource not supported";
  log(String(err));
}

function clearStates() {
  Object.values(nodes).forEach((node) => {
    node.classList.remove("active", "failed", "done");
  });
}

function setActive(state) {
  clearStates();
  nodes[state]?.classList.add("active");
}

function setFailed(state) {
  clearStates();
  nodes[state]?.classList.add("failed");
}

function setDone(state) {
  clearStates();
  nodes[state]?.classList.add("done");
}

async function renderGraph() {
  const res = await fetch(graphUrl);
  const graph = await res.json();

  const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
  const marker = document.createElementNS("http://www.w3.org/2000/svg", "marker");
  marker.setAttribute("id", "arrow");
  marker.setAttribute("markerWidth", "10");
  marker.setAttribute("markerHeight", "7");
  marker.setAttribute("refX", "10");
  marker.setAttribute("refY", "3.5");
  marker.setAttribute("orient", "auto");
  const polygon = document.createElementNS("http://www.w3.org/2000/svg", "polygon");
  polygon.setAttribute("points", "0 0, 10 3.5, 0 7");
  polygon.setAttribute("fill", "#aaa");
  marker.appendChild(polygon);
  defs.appendChild(marker);
  graphEl.appendChild(defs);

  const nodeIds = Object.keys(graph.nodes);
  const width = 600;
  const height = 220;
  const nodeWidth = 110;
  const nodeHeight = 60;
  const gap = nodeIds.length > 1 ? (width - nodeWidth) / (nodeIds.length - 1) : 0;
  const y = (height - nodeHeight) / 2;

  const positions = {};
  nodeIds.forEach((id, idx) => {
    const x = idx * gap;
    positions[id] = { x, y };
  });

  Object.values(graph.edges).forEach((edge) => {
    if (edge.from === "*" || !positions[edge.from] || !positions[edge.to]) {
      return;
    }
    const from = positions[edge.from];
    const to = positions[edge.to];
    const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line.classList.add("edge");
    line.setAttribute("x1", String(from.x + nodeWidth));
    line.setAttribute("y1", String(from.y + nodeHeight / 2));
    line.setAttribute("x2", String(to.x));
    line.setAttribute("y2", String(to.y + nodeHeight / 2));
    graphEl.appendChild(line);
  });

  nodeIds.forEach((id) => {
    const pos = positions[id];
    const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
    rect.classList.add("node");
    rect.setAttribute("id", `node-${id}`);
    rect.setAttribute("x", String(pos.x));
    rect.setAttribute("y", String(pos.y));
    rect.setAttribute("width", String(nodeWidth));
    rect.setAttribute("height", String(nodeHeight));
    graphEl.appendChild(rect);

    const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
    text.setAttribute("x", String(pos.x + nodeWidth / 2));
    text.setAttribute("y", String(pos.y + nodeHeight / 2 + 5));
    text.setAttribute("text-anchor", "middle");
    text.textContent = graph.nodes[id].label ?? id;
    graphEl.appendChild(text);

    nodes[id] = rect;
  });
}
