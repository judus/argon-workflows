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

class LogViewer {
  constructor({ logEl }) {
    this.logEl = logEl;
  }

  append(line) {
    this.logEl.textContent += line + "\n";
    this.logEl.scrollTop = this.logEl.scrollHeight;
  }

  clear() {
    this.logEl.textContent = "";
  }
}

class GraphViewer {
  constructor({ graphEl, statusEl, layout, virtual }) {
    this.graphEl = graphEl;
    this.statusEl = statusEl;
    this.layout = layout;
    this.virtual = virtual;
    this.nodes = {};
    this.edges = {};
    this.firstRealStep = null;
    this.lastRealStep = null;
    this.resizeHandler = null;
  }

  setStatus(text) {
    this.statusEl.textContent = text;
  }

  markUnstarted() {
    this.setActive(this.virtual.start);
  }

  resetRunVisuals() {
    Object.values(this.nodes).forEach((node) => {
      node.classList.remove("active", "failed", "done");
    });

    Object.values(this.edges).forEach((edge) => {
      edge.classList.remove("active", "finished");
    });
  }

  handleEvent(payload) {
    if (payload.type === "run.started") {
      this.resetRunVisuals();
      this.setDone(this.virtual.start);
      if (this.firstRealStep) {
        this.setEdgeFinished(this.virtual.start, this.firstRealStep);
      }
    }

    if (payload.type === "step.started") {
      this.setActive(payload.state);
    }

    if (payload.type === "step.failed") {
      this.setFailed(payload.state);
    }

    if (payload.type === "step.finished") {
      this.setDone(payload.state);
    }

    if (payload.type === "run.finished") {
      this.setDone(this.virtual.end);
      if (this.lastRealStep) {
        this.setEdgeFinished(this.lastRealStep, this.virtual.end);
      }
      Object.values(this.edges).forEach((edge) => edge.classList.remove("active"));
    }

    if (payload.type === "transition.taken") {
      const from = payload.meta?.from;
      const to = payload.meta?.to;

      if (from && to) {
        this.setEdgeFinished(from, to);
        this.setEdgeActive(from, to);
      }
    }
  }

  render(graph) {
    this.graphEl.innerHTML = "";
    this.nodes = {};
    this.edges = {};

    const defs = this.svgEl("defs");
    const marker = this.svgEl("marker");
    marker.setAttribute("id", "arrow");
    marker.setAttribute("markerWidth", String(this.layout.arrowSize));
    marker.setAttribute("markerHeight", String(this.layout.arrowSize));
    marker.setAttribute("markerUnits", "userSpaceOnUse");
    marker.setAttribute("refX", "0");
    marker.setAttribute("refY", String(this.layout.arrowSize / 2));
    marker.setAttribute(
      "viewBox",
      `0 0 ${this.layout.arrowSize} ${this.layout.arrowSize}`,
    );
    marker.setAttribute("orient", "auto");
    const polygon = this.svgEl("polygon");
    polygon.setAttribute(
      "points",
      `0 0, ${this.layout.arrowSize} ${this.layout.arrowSize / 2}, 0 ${this.layout.arrowSize}`,
    );
    polygon.setAttribute("fill", "context-stroke");
    marker.appendChild(polygon);
    defs.appendChild(marker);
    this.graphEl.appendChild(defs);

    const nodeIds = Object.keys(graph.nodes);
    this.firstRealStep = nodeIds[0] ?? null;
    this.lastRealStep = nodeIds[nodeIds.length - 1] ?? null;
    const allNodeIds = [this.virtual.start, ...nodeIds, this.virtual.end];

    let height =
      this.layout.topPadding +
      this.layout.bottomPadding +
      allNodeIds.length * this.layout.nodeHeight;
    for (let i = 0; i < allNodeIds.length - 1; i += 1) {
      const current = allNodeIds[i];
      const next = allNodeIds[i + 1];
      const gap =
        current === this.virtual.start || next === this.virtual.end
          ? this.layout.virtualGap
          : this.layout.verticalGap;
      height += gap;
    }

    this.graphEl.setAttribute("viewBox", `0 0 ${this.layout.width} ${height}`);
    this.updateGraphSize(height);

    if (this.resizeHandler) {
      window.removeEventListener("resize", this.resizeHandler);
    }
    this.resizeHandler = () => this.updateGraphSize(height);
    window.addEventListener("resize", this.resizeHandler);

    const positions = {};
    let cursorY = this.layout.topPadding;
    allNodeIds.forEach((id, idx) => {
      const x = (this.layout.width - this.layout.nodeWidth) / 2;
      positions[id] = { x, y: cursorY };
      const next = allNodeIds[idx + 1];
      if (next) {
        const gap =
          id === this.virtual.start || next === this.virtual.end
            ? this.layout.virtualGap
            : this.layout.verticalGap;
        cursorY += this.layout.nodeHeight + gap;
      }
    });

    const renderEdges = [];
    if (this.firstRealStep) {
      renderEdges.push({ from: this.virtual.start, to: this.firstRealStep });
    }
    Object.values(graph.edges).forEach((edge) => renderEdges.push(edge));
    if (this.lastRealStep) {
      renderEdges.push({ from: this.lastRealStep, to: this.virtual.end });
    }

    renderEdges.forEach((edge) => {
      if (edge.from === "*" || !positions[edge.from] || !positions[edge.to]) {
        return;
      }
      const from = positions[edge.from];
      const to = positions[edge.to];
      const startY = this.getEdgeAnchorY(edge.from, from, "out");
      const endY = this.getEdgeAnchorY(edge.to, to, "in");
      const direction = Math.sign(endY - startY);
      const adjustedEndY =
        direction === 0 ? endY : endY - direction * this.layout.arrowSize;
      const line = this.svgEl("line");
      line.classList.add("edge");
      line.setAttribute("x1", String(from.x + this.layout.nodeWidth / 2));
      line.setAttribute("y1", String(startY));
      line.setAttribute("x2", String(to.x + this.layout.nodeWidth / 2));
      line.setAttribute("y2", String(adjustedEndY));
      this.graphEl.appendChild(line);
      this.edges[`${edge.from}->${edge.to}`] = line;
    });

    allNodeIds.forEach((id) => {
      const pos = positions[id];
      const isVirtual = this.isVirtualNode(id);
      let shape;

      if (isVirtual) {
        const circle = this.svgEl("circle");
        circle.classList.add("node", "virtual");
        circle.setAttribute("id", `node-${id}`);
        circle.setAttribute("cx", String(pos.x + this.layout.nodeWidth / 2));
        circle.setAttribute("cy", String(pos.y + this.layout.nodeHeight / 2));
        circle.setAttribute("r", String(this.layout.nodeHeight / 2));
        this.graphEl.appendChild(circle);
        shape = circle;
      } else {
        const rect = this.svgEl("rect");
        rect.classList.add("node");
        rect.setAttribute("id", `node-${id}`);
        rect.setAttribute("x", String(pos.x));
        rect.setAttribute("y", String(pos.y));
        rect.setAttribute("width", String(this.layout.nodeWidth));
        rect.setAttribute("height", String(this.layout.nodeHeight));
        this.graphEl.appendChild(rect);
        shape = rect;
      }

      const text = this.svgEl("text");
      text.setAttribute("x", String(pos.x + this.layout.nodeWidth / 2));
      text.setAttribute("y", String(pos.y + this.layout.nodeHeight / 2 + 5));
      text.setAttribute("text-anchor", "middle");
      text.textContent = this.formatNodeLabel(graph.nodes[id]?.label ?? id);
      this.graphEl.appendChild(text);

      this.nodes[id] = shape;
    });
  }

  clearStates() {
    Object.values(this.nodes).forEach((node) => {
      node.classList.remove("active", "failed");
    });
  }

  setActive(nodeId) {
    this.clearStates();
    this.nodes?.[nodeId]?.classList.add("active");
  }

  setFailed(nodeId) {
    this.clearStates();
    this.nodes?.[nodeId]?.classList.add("failed");
  }

  setDone(nodeId) {
    this.clearStates();
    const node = this.nodes?.[nodeId];
    if (!node) {
      return;
    }
    node.classList.remove("active", "failed");
    node.classList.add("done");
  }

  setEdgeActive(from, to) {
    Object.values(this.edges).forEach((edge) => edge.classList.remove("active"));
    const key = `${from}->${to}`;
    this.edges[key]?.classList.add("active");
  }

  setEdgeFinished(from, to) {
    const key = `${from}->${to}`;
    this.edges[key]?.classList.add("finished");
  }

  isVirtualNode(id) {
    return id === this.virtual.start || id === this.virtual.end;
  }

  getEdgeAnchorY(id, pos, direction) {
    if (!this.isVirtualNode(id)) {
      return direction === "out" ? pos.y + this.layout.nodeHeight : pos.y;
    }

    const radius = this.layout.nodeHeight / 2;
    const centerY = pos.y + this.layout.nodeHeight / 2;
    return direction === "out" ? centerY + radius : centerY - radius;
  }

  updateGraphSize(contentHeight) {
    const top = this.graphEl.getBoundingClientRect().top;
    const remaining = Math.max(
      this.layout.minHeight,
      window.innerHeight - top - this.layout.bottomMargin,
    );
    const targetHeight = Math.max(
      this.layout.minHeight,
      Math.min(contentHeight, remaining),
    );

    this.graphEl.style.maxHeight = `${remaining}px`;
    this.graphEl.style.height = `${targetHeight}px`;
  }

  formatNodeLabel(raw) {
    if (raw === this.virtual.start) {
      return "START";
    }
    if (raw === this.virtual.end) {
      return "END";
    }
    return raw
      .replaceAll("_", " ")
      .replace(/\b\w/g, (c) => c.toUpperCase());
  }

  svgEl(tag) {
    return document.createElementNS("http://www.w3.org/2000/svg", tag);
  }
}

class WorkflowStream {
  constructor({ url }) {
    this.url = url;
    this.eventSource = null;
    this.listeners = new Set();
    this.onOpen = null;
    this.onError = null;
  }

  connect() {
    if (this.eventSource) {
      return;
    }
    this.eventSource = new EventSource(this.url);
    this.eventSource.addEventListener("open", () => {
      if (this.onOpen) {
        this.onOpen();
      }
    });
    this.eventSource.addEventListener("error", () => {
      if (this.onError) {
        this.onError();
      }
    });
    this.eventSource.addEventListener("workflow", (event) => {
      const payload = JSON.parse(event.data);
      this.listeners.forEach((listener) => listener(payload));
    });
  }

  disconnect() {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
  }

  onEvent(listener) {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }
}

class ArgonGraph extends HTMLElement {
  constructor() {
    super();
    this.graphViewer = null;
  }

  connectedCallback() {
    const graphEl = this.querySelector("svg");
    const statusEl = this.querySelector(".argon-status");
    if (!graphEl || !statusEl) {
      return;
    }
    this.graphViewer = new GraphViewer({
      graphEl,
      statusEl,
      layout: config.layout,
      virtual: config.virtual,
    });
  }

  async loadGraph(graph) {
    if (!this.graphViewer) {
      return;
    }
    await this.graphViewer.render(graph);
    this.graphViewer.markUnstarted();
  }

  setStatus(text) {
    this.graphViewer?.setStatus(text);
  }

  handleEvent(payload) {
    this.graphViewer?.handleEvent(payload);
  }
}

class ArgonLog extends HTMLElement {
  constructor() {
    super();
    this.logViewer = null;
  }

  connectedCallback() {
    const logEl = this.querySelector(".argon-log");
    if (!logEl) {
      return;
    }
    this.logViewer = new LogViewer({ logEl });
  }

  append(line) {
    this.logViewer?.append(line);
  }

  clear() {
    this.logViewer?.clear();
  }
}

customElements.define("argon-graph", ArgonGraph);
customElements.define("argon-log", ArgonLog);

async function init() {
  const graphComponent = document.querySelector("argon-graph");
  const logComponent = document.querySelector("argon-log");

  const graphResponse = await fetch(config.graphUrl);
  const graph = await graphResponse.json();
  await graphComponent?.loadGraph(graph);
  graphComponent?.setStatus(`Status: connecting to ${config.streamUrl}`);

  const stream = new WorkflowStream({ url: config.streamUrl });
  stream.onOpen = () => {
    graphComponent?.setStatus(`Status: connected (${config.streamUrl})`);
  };
  stream.onError = () => {
    graphComponent?.setStatus("Status: disconnected (retrying...)");
  };
  stream.onEvent((payload) => {
    const isRunEvent = payload.type.startsWith("run.");
    const label = isRunEvent ? payload.workflowId : payload.state;
    logComponent?.append(`[${payload.type}] ${label} (run=${payload.runId})`);
    graphComponent?.handleEvent(payload);
  });
  stream.connect();
}

init().catch((err) => {
  const graphComponent = document.querySelector("argon-graph");
  const logComponent = document.querySelector("argon-log");
  graphComponent?.setStatus("Status: EventSource not supported");
  logComponent?.append(String(err));
});
