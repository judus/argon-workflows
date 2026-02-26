const elements = {
  status: document.getElementById("status"),
  log: document.getElementById("log"),
  graph: document.getElementById("graph"),
};

const config = {
  streamUrl: "/api/stream?runId=demo-run",
  graphUrl: "/api/graph",
  layout: {
    width: 360,
    nodeWidth: 180,
    nodeHeight: 60,
    topPadding: 20,
    bottomPadding: 20,
    verticalGap: 36,
    virtualGap: 36,
    arrowSize: 6,
    minHeight: 220,
    bottomMargin: 24,
  },
  virtual: {
    start: "__virtual_start",
    end: "__virtual_end",
  },
};

const state = {
  nodes: {},
  edges: {},
  firstRealStep: null,
  lastRealStep: null,
};

function log(line) {
  elements.log.textContent += line + "\n";
  elements.log.scrollTop = elements.log.scrollHeight;
}

async function init() {
  await renderGraph();
  elements.status.textContent = `Status: connecting to ${config.streamUrl}`;
  setActive(config.virtual.start);

  const es = new EventSource(config.streamUrl);

  es.addEventListener("open", () => {
    elements.status.textContent = `Status: connected (${config.streamUrl})`;
  });

  es.addEventListener("error", () => {
    elements.status.textContent = "Status: disconnected (retrying...)";
  });

  es.addEventListener("workflow", (event) => {
    const payload = JSON.parse(event.data);
    const isRunEvent = payload.type.startsWith("run.");
    const label = isRunEvent ? payload.workflowId : payload.state;
    log(`[${payload.type}] ${label} (run=${payload.runId})`);

    if (payload.type === "run.started") {
      resetRunVisuals();
      setDone(config.virtual.start);
      if (state.firstRealStep) {
        setEdgeFinished(config.virtual.start, state.firstRealStep);
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
      setDone(config.virtual.end);
      if (state.lastRealStep) {
        setEdgeFinished(state.lastRealStep, config.virtual.end);
      }
      Object.values(state.edges).forEach((edge) => edge.classList.remove("active"));
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
  elements.status.textContent = "Status: EventSource not supported";
  log(String(err));
});

function clearStates() {
  Object.values(state.nodes).forEach((node) => {
    node.classList.remove("active", "failed");
  });
}

function setActive(nodeId) {
  clearStates();
  state.nodes?.[nodeId]?.classList.add("active");
}

function setFailed(nodeId) {
  clearStates();
  state.nodes?.[nodeId]?.classList.add("failed");
}

function setDone(nodeId) {
  clearStates();
  const node = state.nodes?.[nodeId];
  if (!node) {
    return;
  }
  node.classList.remove("active", "failed");
  node.classList.add("done");
}

function setEdgeActive(from, to) {
  Object.values(state.edges).forEach((edge) => edge.classList.remove("active"));
  const key = `${from}->${to}`;
  state.edges[key]?.classList.add("active");
}

function setEdgeFinished(from, to) {
  const key = `${from}->${to}`;
  state.edges[key]?.classList.add("finished");
}

function resetRunVisuals() {
  Object.values(state.nodes).forEach((node) => {
    node.classList.remove("active", "failed", "done");
  });

  Object.values(state.edges).forEach((edge) => {
    edge.classList.remove("active", "finished");
  });
}

function isVirtualNode(id) {
  return id === config.virtual.start || id === config.virtual.end;
}

function getEdgeAnchorY(id, pos, direction) {
  if (!isVirtualNode(id)) {
    return direction === "out"
      ? pos.y + config.layout.nodeHeight
      : pos.y;
  }

  const radius = config.layout.nodeHeight / 2;
  const centerY = pos.y + config.layout.nodeHeight / 2;
  return direction === "out" ? centerY + radius : centerY - radius;
}

function updateGraphSize(contentHeight) {
  const top = elements.graph.getBoundingClientRect().top;
  const remaining = Math.max(
    config.layout.minHeight,
    window.innerHeight - top - config.layout.bottomMargin,
  );
  const targetHeight = Math.max(
    config.layout.minHeight,
    Math.min(contentHeight, remaining),
  );

  elements.graph.style.maxHeight = `${remaining}px`;
  elements.graph.style.height = `${targetHeight}px`;
}

async function renderGraph() {
  const res = await fetch(config.graphUrl);
  const graph = await res.json();

  const defs = svgEl("defs");
  const marker = svgEl("marker");
  marker.setAttribute("id", "arrow");
  marker.setAttribute("markerWidth", String(config.layout.arrowSize));
  marker.setAttribute("markerHeight", String(config.layout.arrowSize));
  marker.setAttribute("markerUnits", "userSpaceOnUse");
  marker.setAttribute("refX", "0");
  marker.setAttribute("refY", String(config.layout.arrowSize / 2));
  marker.setAttribute(
    "viewBox",
    `0 0 ${config.layout.arrowSize} ${config.layout.arrowSize}`,
  );
  marker.setAttribute("orient", "auto");
  const polygon = svgEl("polygon");
  polygon.setAttribute(
    "points",
    `0 0, ${config.layout.arrowSize} ${config.layout.arrowSize / 2}, 0 ${config.layout.arrowSize}`,
  );
  polygon.setAttribute("fill", "context-stroke");
  marker.appendChild(polygon);
  defs.appendChild(marker);
  elements.graph.appendChild(defs);

  const nodeIds = Object.keys(graph.nodes);
  state.firstRealStep = nodeIds[0] ?? null;
  state.lastRealStep = nodeIds[nodeIds.length - 1] ?? null;
  const allNodeIds = [config.virtual.start, ...nodeIds, config.virtual.end];
  const {
    width,
    nodeWidth,
    nodeHeight,
    topPadding,
    bottomPadding,
    verticalGap,
    virtualGap,
  } = config.layout;

  let height = topPadding + bottomPadding + allNodeIds.length * nodeHeight;
  for (let i = 0; i < allNodeIds.length - 1; i += 1) {
    const current = allNodeIds[i];
    const next = allNodeIds[i + 1];
    const gap =
      current === config.virtual.start || next === config.virtual.end
        ? virtualGap
        : verticalGap;
    height += gap;
  }

  elements.graph.setAttribute("viewBox", `0 0 ${width} ${height}`);
  updateGraphSize(height);

  window.addEventListener("resize", () => updateGraphSize(height));

  const positions = {};
  let cursorY = topPadding;
  allNodeIds.forEach((id, idx) => {
    const x = (width - nodeWidth) / 2;
    positions[id] = { x, y: cursorY };
    const next = allNodeIds[idx + 1];
    if (next) {
      const gap =
        id === config.virtual.start || next === config.virtual.end
          ? virtualGap
          : verticalGap;
      cursorY += nodeHeight + gap;
    }
  });

  const renderEdges = [];
  if (state.firstRealStep) {
    renderEdges.push({ from: config.virtual.start, to: state.firstRealStep });
  }
  Object.values(graph.edges).forEach((edge) => renderEdges.push(edge));
  if (state.lastRealStep) {
    renderEdges.push({ from: state.lastRealStep, to: config.virtual.end });
  }

  renderEdges.forEach((edge) => {
    if (edge.from === "*" || !positions[edge.from] || !positions[edge.to]) {
      return;
    }
    const from = positions[edge.from];
    const to = positions[edge.to];
    const startY = getEdgeAnchorY(edge.from, from, "out");
    const endY = getEdgeAnchorY(edge.to, to, "in");
    const direction = Math.sign(endY - startY);
    const adjustedEndY =
      direction === 0 ? endY : endY - direction * config.layout.arrowSize;
    const line = svgEl("line");
    line.classList.add("edge");
    line.setAttribute("x1", String(from.x + nodeWidth / 2));
    line.setAttribute("y1", String(startY));
    line.setAttribute("x2", String(to.x + nodeWidth / 2));
    line.setAttribute("y2", String(adjustedEndY));
    elements.graph.appendChild(line);
    state.edges[`${edge.from}->${edge.to}`] = line;
  });

  allNodeIds.forEach((id) => {
    const pos = positions[id];
    const isVirtual = isVirtualNode(id);
    let shape;

    if (isVirtual) {
      const circle = svgEl("circle");
      circle.classList.add("node", "virtual");
      circle.setAttribute("id", `node-${id}`);
      circle.setAttribute("cx", String(pos.x + nodeWidth / 2));
      circle.setAttribute("cy", String(pos.y + nodeHeight / 2));
      circle.setAttribute("r", String(nodeHeight / 2));
      elements.graph.appendChild(circle);
      shape = circle;
    } else {
      const rect = svgEl("rect");
      rect.classList.add("node");
      rect.setAttribute("id", `node-${id}`);
      rect.setAttribute("x", String(pos.x));
      rect.setAttribute("y", String(pos.y));
      rect.setAttribute("width", String(nodeWidth));
      rect.setAttribute("height", String(nodeHeight));
      elements.graph.appendChild(rect);
      shape = rect;
    }

    const text = svgEl("text");
    text.setAttribute("x", String(pos.x + nodeWidth / 2));
    text.setAttribute("y", String(pos.y + nodeHeight / 2 + 5));
    text.setAttribute("text-anchor", "middle");
    text.textContent = formatNodeLabel(graph.nodes[id]?.label ?? id);
    elements.graph.appendChild(text);

    state.nodes[id] = shape;
  });
}

function formatNodeLabel(raw) {
  if (raw === config.virtual.start) {
    return "START";
  }
  if (raw === config.virtual.end) {
    return "END";
  }
  return raw
    .replaceAll("_", " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function svgEl(tag) {
  return document.createElementNS("http://www.w3.org/2000/svg", tag);
}
