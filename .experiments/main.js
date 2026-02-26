const statusEl = document.getElementById("status");
const logEl = document.getElementById("log");
const graphEl = document.getElementById("graph");
const GRAPH_MIN_HEIGHT = 220;
const GRAPH_BOTTOM_MARGIN = 24;
const NODE_HEIGHT = 60;
const VIRTUAL_START = "__start";
const VIRTUAL_END = "__end";
const VIRTUAL_NODE_RADIUS = 36;

const nodes = {};
const edges = {};
let firstRealStep = null;
let lastRealStep = null;

function log(line) {
  logEl.textContent += line + "\n";
  logEl.scrollTop = logEl.scrollHeight;
}

const url = "/api/stream?runId=demo-run";
const graphUrl = "/api/graph";

async function init() {
  await renderGraph();
  statusEl.textContent = `Status: connecting to ${url}`;

  const es = new EventSource(url);

  es.addEventListener("open", () => {
    statusEl.textContent = `Status: connected (${url})`;
  });

  es.addEventListener("error", () => {
    statusEl.textContent = "Status: disconnected (retrying...)";
  });

  es.addEventListener("workflow", (event) => {
    const payload = JSON.parse(event.data);
    const isRunEvent = payload.type.startsWith("run.");
    const label = isRunEvent ? payload.workflowId : payload.state;
    log(`[${payload.type}] ${label} (run=${payload.runId})`);

    if (payload.type === "run.started") {
      resetRunVisuals();
      setDone(VIRTUAL_START);
      if (firstRealStep) {
        setEdgeFinished(VIRTUAL_START, firstRealStep);
      }
    }

    if (payload.type === "step.started") {
      setActive(payload.state);
    }

    if (payload.type === "step.failed") {
      setFailed(payload.state);
    }

    if (payload.type === "step.finished") {
      setDone(payload.state);
    }

    if (payload.type === "run.finished") {
      setDone(VIRTUAL_END);
      if (lastRealStep) {
        setEdgeFinished(lastRealStep, VIRTUAL_END);
      }
      Object.values(edges).forEach((edge) => edge.classList.remove("active"));
    }

    if (payload.type === "transition.taken") {
      const from = payload.meta?.from;
      const to = payload.meta?.to;

      if (from && to) {
        setEdgeFinished(from, to);
        setEdgeActive(from, to);
      }
    }
  });
}

init().catch((err) => {
  statusEl.textContent = "Status: EventSource not supported";
  log(String(err));
});

function clearStates() {
  Object.values(nodes).forEach((node) => {
    node.classList.remove("active", "failed");
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
  const node = nodes[state];
  if (!node) {
    return;
  }
  node.classList.remove("active", "failed");
  node.classList.add("done");
}

function setEdgeActive(from, to) {
  Object.values(edges).forEach((edge) => edge.classList.remove("active"));
  const key = `${from}->${to}`;
  edges[key]?.classList.add("active");
}

function setEdgeFinished(from, to) {
  const key = `${from}->${to}`;
  edges[key]?.classList.add("finished");
}

function resetRunVisuals() {
  Object.values(nodes).forEach((node) => {
    node.classList.remove("active", "failed", "done");
  });

  Object.values(edges).forEach((edge) => {
    edge.classList.remove("active", "finished");
  });
}

function isVirtualNode(id) {
  return id === VIRTUAL_START || id === VIRTUAL_END;
}

function getEdgeAnchorY(id, pos, direction) {
  if (!isVirtualNode(id)) {
    return direction === "out" ? pos.y + NODE_HEIGHT : pos.y;
  }

  const centerY = pos.y + NODE_HEIGHT / 2;
  return direction === "out"
    ? centerY + VIRTUAL_NODE_RADIUS
    : centerY - VIRTUAL_NODE_RADIUS;
}

function updateGraphSize(contentHeight) {
  const top = graphEl.getBoundingClientRect().top;
  const remaining = Math.max(
    GRAPH_MIN_HEIGHT,
    window.innerHeight - top - GRAPH_BOTTOM_MARGIN,
  );
  const targetHeight = Math.max(
    GRAPH_MIN_HEIGHT,
    Math.min(contentHeight, remaining),
  );

  graphEl.style.maxHeight = `${remaining}px`;
  graphEl.style.height = `${targetHeight}px`;
}

async function renderGraph() {
  const res = await fetch(graphUrl);
  const graph = await res.json();

  const defs = document.createElementNS("http://www.w3.org/2000/svg", "defs");
  const marker = document.createElementNS("http://www.w3.org/2000/svg", "marker");
  marker.setAttribute("id", "arrow");
  marker.setAttribute("markerWidth", "10");
  marker.setAttribute("markerHeight", "7");
  marker.setAttribute("markerUnits", "userSpaceOnUse");
  marker.setAttribute("refX", "10");
  marker.setAttribute("refY", "3.5");
  marker.setAttribute("orient", "auto");
  const polygon = document.createElementNS("http://www.w3.org/2000/svg", "polygon");
  polygon.setAttribute("points", "0 0, 10 3.5, 0 7");
  polygon.setAttribute("fill", "context-stroke");
  marker.appendChild(polygon);
  defs.appendChild(marker);
  graphEl.appendChild(defs);

  const nodeIds = Object.keys(graph.nodes);
  firstRealStep = nodeIds[0] ?? null;
  lastRealStep = nodeIds[nodeIds.length - 1] ?? null;
  const allNodeIds = [VIRTUAL_START, ...nodeIds, VIRTUAL_END];
  const width = 360;
  const nodeWidth = 180;
  const nodeHeight = NODE_HEIGHT;
  const topPadding = 20;
  const bottomPadding = 20;
  const verticalGap = 36;
  const height =
    topPadding +
    bottomPadding +
    allNodeIds.length * nodeHeight +
    Math.max(0, allNodeIds.length - 1) * verticalGap;

  graphEl.setAttribute("viewBox", `0 0 ${width} ${height}`);
  updateGraphSize(height);

  window.addEventListener("resize", () => {
    updateGraphSize(height);
  });

  const positions = {};
  allNodeIds.forEach((id, idx) => {
    const x = (width - nodeWidth) / 2;
    const y = topPadding + idx * (nodeHeight + verticalGap);
    positions[id] = { x, y };
  });

  const renderEdges = [];
  if (firstRealStep) {
    renderEdges.push({ from: VIRTUAL_START, to: firstRealStep });
  }
  Object.values(graph.edges).forEach((edge) => renderEdges.push(edge));
  if (lastRealStep) {
    renderEdges.push({ from: lastRealStep, to: VIRTUAL_END });
  }

  renderEdges.forEach((edge) => {
    if (edge.from === "*" || !positions[edge.from] || !positions[edge.to]) {
      return;
    }
    const from = positions[edge.from];
    const to = positions[edge.to];
    const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
    line.classList.add("edge");
    line.setAttribute("x1", String(from.x + nodeWidth / 2));
    line.setAttribute("y1", String(getEdgeAnchorY(edge.from, from, "out")));
    line.setAttribute("x2", String(to.x + nodeWidth / 2));
    line.setAttribute("y2", String(getEdgeAnchorY(edge.to, to, "in")));
    graphEl.appendChild(line);
    edges[`${edge.from}->${edge.to}`] = line;
  });

  allNodeIds.forEach((id) => {
    const pos = positions[id];
    const isVirtual = id === VIRTUAL_START || id === VIRTUAL_END;
    let shape;

    if (isVirtual) {
      const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
      circle.classList.add("node", "virtual");
      circle.setAttribute("id", `node-${id}`);
      circle.setAttribute("cx", String(pos.x + nodeWidth / 2));
      circle.setAttribute("cy", String(pos.y + nodeHeight / 2));
      circle.setAttribute("r", String(VIRTUAL_NODE_RADIUS));
      graphEl.appendChild(circle);
      shape = circle;
    } else {
      const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
      rect.classList.add("node");
      rect.setAttribute("id", `node-${id}`);
      rect.setAttribute("x", String(pos.x));
      rect.setAttribute("y", String(pos.y));
      rect.setAttribute("width", String(nodeWidth));
      rect.setAttribute("height", String(nodeHeight));
      graphEl.appendChild(rect);
      shape = rect;
    }

    const text = document.createElementNS("http://www.w3.org/2000/svg", "text");
    text.setAttribute("x", String(pos.x + nodeWidth / 2));
    text.setAttribute("y", String(pos.y + nodeHeight / 2 + 5));
    text.setAttribute("text-anchor", "middle");
    text.textContent = formatNodeLabel(graph.nodes[id]?.label ?? id);
    graphEl.appendChild(text);

    nodes[id] = shape;
  });
}

function formatNodeLabel(raw) {
  if (raw === VIRTUAL_START) {
    return "START";
  }
  if (raw === VIRTUAL_END) {
    return "END";
  }
  return raw
    .replaceAll("_", " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
}
