<template>
  <div class="whiteboard-container">
    <div class="whiteboard-toolbar card mb-3 p-3">
      <div class="row g-3 align-items-center">
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-bold">Tableaux</label>
          <div class="d-flex gap-2">
            <select
              class="form-select"
              v-model="selectedWhiteboardId"
              @change="loadWhiteboard"
            >
              <option :value="null">Nouveau tableau</option>
              <option v-for="board in whiteboards" :key="board.id" :value="board.id">
                {{ board.title || board.name || `Tableau #${board.id}` }}
              </option>
            </select>
            <button type="button" class="btn btn-outline-secondary" @click="createNewWhiteboard">
              Réinitialiser
            </button>
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-bold">Titre</label>
          <input
            type="text"
            class="form-control"
            v-model="whiteboardTitle"
            placeholder="Titre du tableau"
          />
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-bold">Outils</label>
          <div class="d-flex flex-wrap gap-2">
            <select class="form-select tool-select" v-model="activeTool">
              <option value="pen">Stylo</option>
              <option value="eraser">Gomme</option>
            </select>
            <input
              type="color"
              class="form-control form-control-color"
              v-model="drawColor"
              :disabled="activeTool === 'eraser'"
              title="Couleur du trait"
            />
            <input
              type="range"
              class="form-range"
              min="1"
              max="40"
              v-model.number="lineWidth"
            />
          </div>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label fw-bold">Actions</label>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary" @click="addNote">
              Ajouter un post-it
            </button>
            <label class="btn btn-outline-secondary mb-0">
              Importer une image
              <input
                ref="imageInput"
                type="file"
                accept="image/*"
                class="d-none"
                @change="handleImageUpload"
              />
            </label>
            <button type="button" class="btn btn-success" :disabled="isSaving" @click="saveWhiteboard">
              {{ isSaving ? 'Enregistrement…' : 'Enregistrer' }}
            </button>
            <button
              type="button"
              class="btn btn-outline-success"
              :disabled="!selectedWhiteboardId"
              @click="saveSnapshot"
            >
              Snapshot
            </button>
            <button type="button" class="btn btn-outline-dark" @click="downloadState">
              Export JSON
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="whiteboard-main">
      <div class="canvas-wrapper" ref="canvasWrapper">
        <canvas
          ref="canvas"
          :width="canvasWidth"
          :height="canvasHeight"
          @mousedown.prevent="startDrawing"
          @mousemove.prevent="draw"
          @mouseup.prevent="stopDrawing"
          @mouseleave.prevent="stopDrawing"
          @touchstart.prevent="startDrawing"
          @touchmove.prevent="draw"
          @touchend.prevent="stopDrawing"
          @touchcancel.prevent="stopDrawing"
        ></canvas>

        <div
          v-for="note in notes"
          :key="note.id"
          class="sticky-note"
          :style="{
            left: note.x + 'px',
            top: note.y + 'px',
            backgroundColor: note.color
          }"
        >
          <div
            class="sticky-note__header"
            @mousedown.stop.prevent="beginNoteDrag(note, $event)"
            @touchstart.stop.prevent="beginNoteDrag(note, $event)"
          >
            <span>Post-it</span>
            <button type="button" class="btn-close" aria-label="Fermer" @click="removeNote(note.id)"></button>
          </div>
          <textarea v-model="note.text" placeholder="Saisir une note"></textarea>
        </div>

        <div
          v-for="image in images"
          :key="image.id"
          class="board-image"
          :style="{ left: image.x + 'px', top: image.y + 'px' }"
        >
          <div
            class="board-image__header"
            @mousedown.stop.prevent="beginImageDrag(image, $event)"
            @touchstart.stop.prevent="beginImageDrag(image, $event)"
          >
            <span class="text-truncate">{{ image.name }}</span>
            <button type="button" class="btn-close" aria-label="Fermer" @click="removeImage(image.id)"></button>
          </div>
          <img :src="image.src" :alt="image.name" :style="{ width: image.width + 'px' }" />
        </div>
      </div>

      <aside class="snapshot-panel">
        <div class="snapshot-panel__section">
          <h5>Snapshots</h5>
          <p v-if="!snapshotHistory.length" class="text-muted">Aucun snapshot disponible.</p>
          <ul v-else class="list-unstyled snapshot-list">
            <li v-for="snapshot in snapshotHistory" :key="snapshot.id">
              <button type="button" class="btn btn-link p-0" @click="loadSnapshot(snapshot)">
                {{ formatSnapshotLabel(snapshot) }}
              </button>
            </li>
          </ul>
        </div>
        <div class="snapshot-panel__section">
          <h5>Fichiers</h5>
          <input
            type="file"
            class="form-control"
            multiple
            :disabled="!selectedWhiteboardId"
            @change="uploadFiles"
          />
          <p v-if="!boardFiles.length" class="text-muted mt-2">Aucun fichier associé.</p>
          <ul v-else class="list-unstyled mt-2 file-list">
            <li v-for="file in boardFiles" :key="file.id">
              <a :href="file.url || file.path" target="_blank" rel="noopener">{{ file.name || file.original_name }}</a>
            </li>
          </ul>
        </div>
      </aside>
    </div>

    <div v-if="isLoading" class="alert alert-info mt-3">Chargement…</div>
    <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
  </div>
</template>

<script>
import axios from 'axios';

const DEFAULT_ENDPOINTS = {
  index: '/api/collaboration/whiteboards',
  store: '/api/collaboration/whiteboards',
  show: '/api/collaboration/whiteboards/{id}',
  update: '/api/collaboration/whiteboards/{id}',
  snapshots: '/api/collaboration/whiteboards/{id}/snapshots',
  files: '/api/collaboration/whiteboards/{id}/files'
};

function parseMaybeJson(value) {
  if (!value) {
    return null;
  }

  if (typeof value === 'object') {
    return value;
  }

  try {
    return JSON.parse(value);
  } catch (e) {
    return null;
  }
}

export default {
  name: 'Whiteboard',
  props: {
    initialWhiteboardId: {
      type: [Number, String],
      default: null
    },
    initialWhiteboard: {
      type: [Object, String, null],
      default: null
    },
    initialSnapshots: {
      type: Array,
      default: () => []
    },
    initialFiles: {
      type: Array,
      default: () => []
    },
    endpoints: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    const initialBoard = parseMaybeJson(this.initialWhiteboard);
    const mergedEndpoints = {
      ...DEFAULT_ENDPOINTS,
      ...(window.whiteboardEndpoints || {}),
      ...(this.endpoints || {})
    };

    return {
      canvasWidth: 1200,
      canvasHeight: 700,
      canvasContext: null,
      canvasElement: null,
      isDrawing: false,
      activeTool: 'pen',
      drawColor: '#1f2937',
      lineWidth: 4,
      lastPointerPosition: null,
      notes: [],
      images: [],
      noteIdCounter: 1,
      imageIdCounter: 1,
      draggingNote: null,
      draggingImage: null,
      dragOffset: { x: 0, y: 0 },
      noteDragHandler: null,
      stopNoteDragHandler: null,
      imageDragHandler: null,
      stopImageDragHandler: null,
      whiteboards: [],
      selectedWhiteboardId: initialBoard?.id || (this.initialWhiteboardId ? Number(this.initialWhiteboardId) : null),
      whiteboardTitle: initialBoard?.title || initialBoard?.name || 'Nouveau tableau',
      snapshotHistory: Array.isArray(this.initialSnapshots) ? [...this.initialSnapshots] : [],
      boardFiles: Array.isArray(this.initialFiles) ? [...this.initialFiles] : [],
      isLoading: false,
      isSaving: false,
      error: null,
      endpointsConfig: mergedEndpoints,
      initialBoardState: initialBoard
    };
  },
  watch: {
    drawColor(newColor) {
      if (this.canvasContext && this.activeTool === 'pen') {
        this.canvasContext.strokeStyle = newColor;
      }
    },
    lineWidth(newWidth) {
      if (this.canvasContext) {
        this.canvasContext.lineWidth = newWidth;
      }
    },
    activeTool(newTool) {
      if (!this.canvasContext) {
        return;
      }
      this.canvasContext.globalCompositeOperation = newTool === 'eraser' ? 'destination-out' : 'source-over';
      if (newTool === 'pen') {
        this.canvasContext.strokeStyle = this.drawColor;
      }
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.setupCanvas();
      window.addEventListener('resize', this.handleWindowResize, { passive: true });
      window.addEventListener('mouseup', this.stopDrawing, { passive: true });
      window.addEventListener('touchend', this.stopDrawing, { passive: true });

      if (this.initialBoardState) {
        this.applyBoard(this.initialBoardState);
      } else if (this.selectedWhiteboardId) {
        this.loadWhiteboard(this.selectedWhiteboardId);
      }

      this.fetchWhiteboards();
    });
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleWindowResize);
    window.removeEventListener('mouseup', this.stopDrawing);
    window.removeEventListener('touchend', this.stopDrawing);
    this.removeNoteDragListeners();
    this.removeImageDragListeners();
  },
  methods: {
    handleWindowResize() {
      this.setupCanvas(true);
    },
    setupCanvas(preserveDrawing = false) {
      const canvas = this.$refs.canvas;
      const wrapper = this.$refs.canvasWrapper;
      if (!canvas || !wrapper) {
        return;
      }

      let previousDrawing = null;
      if (preserveDrawing && this.canvasElement) {
        previousDrawing = this.canvasElement.toDataURL('image/png');
      }

      const width = wrapper.clientWidth || this.canvasWidth;
      const height = wrapper.clientHeight || 600;
      this.canvasWidth = width;
      this.canvasHeight = height;

      const dpr = window.devicePixelRatio || 1;
      canvas.width = width * dpr;
      canvas.height = height * dpr;
      canvas.style.width = `${width}px`;
      canvas.style.height = `${height}px`;

      const context = canvas.getContext('2d');
      context.setTransform(1, 0, 0, 1, 0, 0);
      context.scale(dpr, dpr);
      context.lineCap = 'round';
      context.lineJoin = 'round';
      context.lineWidth = this.lineWidth;
      context.strokeStyle = this.drawColor;
      context.globalCompositeOperation = this.activeTool === 'eraser' ? 'destination-out' : 'source-over';

      this.canvasElement = canvas;
      this.canvasContext = context;

      if (previousDrawing) {
        const img = new Image();
        img.onload = () => {
          context.clearRect(0, 0, width, height);
          context.drawImage(img, 0, 0, width, height);
        };
        img.src = previousDrawing;
      } else {
        context.clearRect(0, 0, width, height);
      }
    },
    getRelativePosition(event) {
      const wrapper = this.$refs.canvasWrapper;
      if (!wrapper) {
        return { x: 0, y: 0 };
      }

      const rect = wrapper.getBoundingClientRect();
      const sourceEvent = event.touches && event.touches.length ? event.touches[0] : event;
      const x = sourceEvent.clientX - rect.left;
      const y = sourceEvent.clientY - rect.top;

      return {
        x: Math.min(Math.max(0, x), this.canvasWidth),
        y: Math.min(Math.max(0, y), this.canvasHeight)
      };
    },
    startDrawing(event) {
      if (!this.canvasContext) {
        return;
      }
      this.isDrawing = true;
      const position = this.getRelativePosition(event);
      this.lastPointerPosition = position;
      this.canvasContext.beginPath();
      this.canvasContext.moveTo(position.x, position.y);
    },
    draw(event) {
      if (!this.isDrawing || !this.canvasContext) {
        return;
      }

      const position = this.getRelativePosition(event);
      if (this.activeTool === 'eraser') {
        this.canvasContext.globalCompositeOperation = 'destination-out';
        this.canvasContext.strokeStyle = 'rgba(0,0,0,1)';
      } else {
        this.canvasContext.globalCompositeOperation = 'source-over';
        this.canvasContext.strokeStyle = this.drawColor;
      }

      this.canvasContext.lineWidth = this.lineWidth;
      this.canvasContext.lineTo(position.x, position.y);
      this.canvasContext.stroke();
      this.lastPointerPosition = position;
    },
    stopDrawing() {
      if (!this.isDrawing || !this.canvasContext) {
        return;
      }

      this.canvasContext.closePath();
      this.canvasContext.globalCompositeOperation = this.activeTool === 'eraser' ? 'destination-out' : 'source-over';
      this.isDrawing = false;
      this.lastPointerPosition = null;
    },
    randomNoteColor() {
      const palette = ['#fed766', '#ff928b', '#9ad0ec', '#c6f68d', '#f7aef8'];
      return palette[Math.floor(Math.random() * palette.length)];
    },
    addNote() {
      const x = 40 * this.noteIdCounter;
      const y = 40 * this.noteIdCounter;
      const noteWidth = 200;
      const noteHeight = 160;
      const maxX = Math.max(0, this.canvasWidth - noteWidth);
      const maxY = Math.max(0, this.canvasHeight - noteHeight);
      this.notes.push({
        id: this.generateNoteId(),
        text: '',
        x: Math.max(0, Math.min(x, maxX)),
        y: Math.max(0, Math.min(y, maxY)),
        color: this.randomNoteColor()
      });
    },
    generateNoteId() {
      return this.noteIdCounter++;
    },
    removeNote(id) {
      this.notes = this.notes.filter(note => note.id !== id);
    },
    beginNoteDrag(note, event) {
      this.draggingNote = note;
      const { x, y } = this.getRelativePosition(event);
      this.dragOffset = {
        x: x - note.x,
        y: y - note.y
      };
      this.addNoteDragListeners();
    },
    addNoteDragListeners() {
      this.noteDragHandler = this.noteDragHandler || (evt => this.handleNoteDrag(evt));
      this.stopNoteDragHandler = this.stopNoteDragHandler || (() => this.stopNoteDrag());
      window.addEventListener('mousemove', this.noteDragHandler);
      window.addEventListener('touchmove', this.noteDragHandler, { passive: false });
      window.addEventListener('mouseup', this.stopNoteDragHandler, { passive: true });
      window.addEventListener('touchend', this.stopNoteDragHandler, { passive: true });
    },
    removeNoteDragListeners() {
      if (this.noteDragHandler) {
        window.removeEventListener('mousemove', this.noteDragHandler);
        window.removeEventListener('touchmove', this.noteDragHandler);
      }
      if (this.stopNoteDragHandler) {
        window.removeEventListener('mouseup', this.stopNoteDragHandler);
        window.removeEventListener('touchend', this.stopNoteDragHandler);
      }
    },
    handleNoteDrag(event) {
      if (!this.draggingNote) {
        return;
      }
      event.preventDefault();
      const { x, y } = this.getRelativePosition(event);
      const noteWidth = 200;
      const noteHeight = 160;
      const maxX = Math.max(0, this.canvasWidth - noteWidth);
      const maxY = Math.max(0, this.canvasHeight - noteHeight);
      this.draggingNote.x = Math.min(Math.max(0, x - this.dragOffset.x), maxX);
      this.draggingNote.y = Math.min(Math.max(0, y - this.dragOffset.y), maxY);
    },
    stopNoteDrag() {
      this.removeNoteDragListeners();
      this.draggingNote = null;
    },
    beginImageDrag(image, event) {
      this.draggingImage = image;
      const { x, y } = this.getRelativePosition(event);
      this.dragOffset = {
        x: x - image.x,
        y: y - image.y
      };
      this.addImageDragListeners();
    },
    addImageDragListeners() {
      this.imageDragHandler = this.imageDragHandler || (evt => this.handleImageDrag(evt));
      this.stopImageDragHandler = this.stopImageDragHandler || (() => this.stopImageDrag());
      window.addEventListener('mousemove', this.imageDragHandler);
      window.addEventListener('touchmove', this.imageDragHandler, { passive: false });
      window.addEventListener('mouseup', this.stopImageDragHandler, { passive: true });
      window.addEventListener('touchend', this.stopImageDragHandler, { passive: true });
    },
    removeImageDragListeners() {
      if (this.imageDragHandler) {
        window.removeEventListener('mousemove', this.imageDragHandler);
        window.removeEventListener('touchmove', this.imageDragHandler);
      }
      if (this.stopImageDragHandler) {
        window.removeEventListener('mouseup', this.stopImageDragHandler);
        window.removeEventListener('touchend', this.stopImageDragHandler);
      }
    },
    handleImageDrag(event) {
      if (!this.draggingImage) {
        return;
      }
      event.preventDefault();
      const { x, y } = this.getRelativePosition(event);
      const imageWidth = this.draggingImage.width || 200;
      const imageHeight = this.draggingImage.height || 160;
      const maxX = Math.max(0, this.canvasWidth - imageWidth);
      const maxY = Math.max(0, this.canvasHeight - imageHeight);
      this.draggingImage.x = Math.min(Math.max(0, x - this.dragOffset.x), maxX);
      this.draggingImage.y = Math.min(Math.max(0, y - this.dragOffset.y), maxY);
    },
    stopImageDrag() {
      this.removeImageDragListeners();
      this.draggingImage = null;
    },
    removeImage(id) {
      this.images = this.images.filter(image => image.id !== id);
    },
    handleImageUpload(event) {
      const files = Array.from(event.target.files || []);
      if (!files.length) {
        return;
      }

      files.forEach(file => {
        const reader = new FileReader();
        reader.onload = uploadEvent => {
          const src = uploadEvent.target.result;
          const previewImage = new Image();
          previewImage.onload = () => {
            const maxWidth = 240;
            const ratio = previewImage.width > maxWidth ? maxWidth / previewImage.width : 1;
            this.images.push({
              id: this.imageIdCounter++,
              src,
              name: file.name,
              x: 40 * this.images.length,
              y: 40 * this.images.length,
              width: Math.round(previewImage.width * ratio),
              height: Math.round(previewImage.height * ratio)
            });
          };
          previewImage.src = src;
        };
        reader.readAsDataURL(file);
      });

      if (this.$refs.imageInput) {
        this.$refs.imageInput.value = '';
      }
    },
    resolveEndpoint(name, id = null) {
      const template = this.endpointsConfig[name] || DEFAULT_ENDPOINTS[name];
      if (!template) {
        return null;
      }
      if (typeof template === 'function') {
        return template(id);
      }
      if (id === null || id === undefined) {
        return template;
      }
      const identifier = String(id);
      return template
        .replace('{id}', identifier)
        .replace('{whiteboard}', identifier)
        .replace(':id', identifier)
        .replace(':whiteboard', identifier);
    },
    normalizeCollectionResponse(response) {
      const payload = response?.data ?? response;
      if (Array.isArray(payload)) {
        return payload;
      }
      if (Array.isArray(payload?.data)) {
        return payload.data;
      }
      if (Array.isArray(payload?.data?.data)) {
        return payload.data.data;
      }
      return [];
    },
    normalizeBoardResponse(response) {
      const payload = response?.data ?? response;
      if (!payload) {
        return null;
      }
      if (payload.data && !Array.isArray(payload.data)) {
        return payload.data;
      }
      if (payload.board) {
        return payload.board;
      }
      if (Array.isArray(payload.data)) {
        return payload.data[0] || null;
      }
      return payload;
    },
    normalizeSnapshotResponse(response) {
      const payload = response?.data ?? response;
      if (!payload) {
        return null;
      }
      if (payload.data && !Array.isArray(payload.data)) {
        return payload.data;
      }
      return payload;
    },
    async fetchWhiteboards() {
      const url = this.resolveEndpoint('index');
      if (!url) {
        return;
      }
      this.isLoading = true;
      this.error = null;
      try {
        const response = await axios.get(url);
        this.whiteboards = this.normalizeCollectionResponse(response);
        if (this.selectedWhiteboardId) {
          const exists = this.whiteboards.some(board => Number(board.id) === Number(this.selectedWhiteboardId));
          if (!exists && this.initialBoardState) {
            this.whiteboards.unshift(this.initialBoardState);
          }
        }
      } catch (error) {
        this.handleError(error, "Impossible de charger la liste des tableaux.");
      } finally {
        this.isLoading = false;
      }
    },
    async loadWhiteboard(id = null) {
      const boardId = Number(id || this.selectedWhiteboardId);
      if (!boardId) {
        this.createNewWhiteboard();
        return;
      }
      const url = this.resolveEndpoint('show', boardId);
      if (!url) {
        return;
      }

      this.isLoading = true;
      this.error = null;
      try {
        const response = await axios.get(url);
        const board = this.normalizeBoardResponse(response);
        if (board) {
          this.applyBoard(board);
          this.selectedWhiteboardId = Number(board.id);
          await Promise.all([this.fetchSnapshots(board.id), this.fetchFiles(board.id)]);
        }
      } catch (error) {
        this.handleError(error, "Impossible de charger le tableau sélectionné.");
      } finally {
        this.isLoading = false;
      }
    },
    applyBoard(board) {
      if (!board) {
        this.createNewWhiteboard();
        return;
      }

      this.initialBoardState = board;
      this.whiteboardTitle = board.title || board.name || this.whiteboardTitle || 'Nouveau tableau';
      const state = parseMaybeJson(board.state) || board.payload || board.content || null;
      if (state) {
        this.applyState(state);
      } else {
        this.applyState({
          notes: board.notes,
          images: board.images,
          canvas: board.canvas
        });
      }

      if (Array.isArray(board.snapshots)) {
        this.snapshotHistory = board.snapshots;
      }
      if (Array.isArray(board.files)) {
        this.boardFiles = board.files;
      }
      this.refreshBoardList(board);
    },
    applyState(state) {
      if (!state) {
        this.clearBoard();
        return;
      }

      if (state.title) {
        this.whiteboardTitle = state.title;
      }

      if (Array.isArray(state.notes)) {
        this.notes = state.notes.map(note => ({
          id: note.id || this.generateNoteId(),
          text: note.text || '',
          x: typeof note.x === 'number' ? note.x : 40,
          y: typeof note.y === 'number' ? note.y : 40,
          color: note.color || this.randomNoteColor()
        }));
        const maxId = this.notes.reduce((acc, note) => Math.max(acc, note.id), 0);
        this.noteIdCounter = maxId + 1;
      } else {
        this.notes = [];
      }

      if (Array.isArray(state.images)) {
        this.images = state.images.map(image => ({
          id: image.id || this.imageIdCounter++,
          src: image.src,
          name: image.name || 'Image',
          x: typeof image.x === 'number' ? image.x : 40,
          y: typeof image.y === 'number' ? image.y : 40,
          width: image.width || 200,
          height: image.height || 150
        }));
        const maxImageId = this.images.reduce((acc, img) => Math.max(acc, img.id), 0);
        this.imageIdCounter = maxImageId + 1;
      } else {
        this.images = [];
      }

      if (state.canvas) {
        this.drawCanvasFromData(state.canvas);
      } else {
        this.clearCanvas();
      }
    },
    drawCanvasFromData(dataUrl) {
      if (!this.canvasContext || !dataUrl) {
        return;
      }
      const image = new Image();
      image.onload = () => {
        this.canvasContext.clearRect(0, 0, this.canvasWidth, this.canvasHeight);
        this.canvasContext.drawImage(image, 0, 0, this.canvasWidth, this.canvasHeight);
      };
      image.src = dataUrl;
    },
    clearCanvas() {
      if (!this.canvasContext) {
        return;
      }
      const previousComposite = this.canvasContext.globalCompositeOperation;
      this.canvasContext.globalCompositeOperation = 'source-over';
      this.canvasContext.clearRect(0, 0, this.canvasWidth, this.canvasHeight);
      this.canvasContext.globalCompositeOperation = previousComposite;
    },
    clearBoard() {
      this.clearCanvas();
      this.notes = [];
      this.images = [];
      this.snapshotHistory = [];
      this.boardFiles = [];
      this.noteIdCounter = 1;
      this.imageIdCounter = 1;
      this.whiteboardTitle = 'Nouveau tableau';
    },
    serializeState() {
      const notes = this.notes.map(note => ({
        id: note.id,
        text: note.text,
        x: note.x,
        y: note.y,
        color: note.color
      }));
      const images = this.images.map(image => ({
        id: image.id,
        src: image.src,
        name: image.name,
        x: image.x,
        y: image.y,
        width: image.width,
        height: image.height
      }));

      return {
        title: this.whiteboardTitle,
        canvas: this.canvasElement ? this.canvasElement.toDataURL('image/png') : null,
        notes,
        images
      };
    },
    async saveWhiteboard() {
      const payload = {
        title: this.whiteboardTitle,
        state: this.serializeState()
      };

      let request;
      let url;
      if (this.selectedWhiteboardId) {
        url = this.resolveEndpoint('update', this.selectedWhiteboardId);
        request = () => axios.put(url, payload);
      } else {
        url = this.resolveEndpoint('store');
        request = () => axios.post(url, payload);
      }

      if (!url) {
        return;
      }

      this.isSaving = true;
      this.error = null;
      try {
        const response = await request();
        const board = this.normalizeBoardResponse(response);
        if (board) {
          this.selectedWhiteboardId = Number(board.id);
          this.applyBoard(board);
          await Promise.all([this.fetchSnapshots(board.id), this.fetchFiles(board.id)]);
        }
      } catch (error) {
        this.handleError(error, "Une erreur est survenue lors de l'enregistrement du tableau.");
      } finally {
        this.isSaving = false;
      }
    },
    async saveSnapshot() {
      if (!this.selectedWhiteboardId) {
        this.error = 'Enregistrez le tableau avant de créer un snapshot.';
        return;
      }
      const url = this.resolveEndpoint('snapshots', this.selectedWhiteboardId);
      if (!url) {
        return;
      }
      try {
        const response = await axios.post(url, this.serializeState());
        const snapshot = this.normalizeSnapshotResponse(response);
        if (snapshot) {
          this.snapshotHistory = [snapshot, ...this.snapshotHistory];
        }
      } catch (error) {
        this.handleError(error, "Impossible de créer un snapshot.");
      }
    },
    async fetchSnapshots(whiteboardId) {
      if (!whiteboardId) {
        return;
      }
      const url = this.resolveEndpoint('snapshots', whiteboardId);
      if (!url) {
        return;
      }
      try {
        const response = await axios.get(url);
        const snapshots = this.normalizeCollectionResponse(response);
        if (Array.isArray(snapshots) && snapshots.length) {
          this.snapshotHistory = snapshots;
        }
      } catch (error) {
        this.handleError(error, "Impossible de récupérer les snapshots.");
      }
    },
    loadSnapshot(snapshot) {
      if (!snapshot) {
        return;
      }
      const state = parseMaybeJson(snapshot.state || snapshot.payload || snapshot.content);
      if (state) {
        this.applyState(state);
      }
    },
    async uploadFiles(event) {
      if (!this.selectedWhiteboardId) {
        this.error = 'Enregistrez le tableau avant d\'importer des fichiers.';
        return;
      }
      const files = event.target.files;
      if (!files || !files.length) {
        return;
      }
      const url = this.resolveEndpoint('files', this.selectedWhiteboardId);
      if (!url) {
        return;
      }

      const formData = new FormData();
      Array.from(files).forEach(file => {
        formData.append('files[]', file);
      });
      try {
        const response = await axios.post(url, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        const uploaded = this.normalizeCollectionResponse(response) || [];
        this.boardFiles = [...uploaded, ...this.boardFiles];
      } catch (error) {
        this.handleError(error, "Impossible de téléverser les fichiers.");
      } finally {
        event.target.value = '';
      }
    },
    async fetchFiles(whiteboardId) {
      const url = this.resolveEndpoint('files', whiteboardId);
      if (!url) {
        return;
      }
      try {
        const response = await axios.get(url);
        const files = this.normalizeCollectionResponse(response);
        if (Array.isArray(files)) {
          this.boardFiles = files;
        }
      } catch (error) {
        this.handleError(error, "Impossible de récupérer les fichiers associés.");
      }
    },
    refreshBoardList(board) {
      if (!board || !board.id) {
        return;
      }
      const index = this.whiteboards.findIndex(item => Number(item.id) === Number(board.id));
      if (index >= 0) {
        this.whiteboards.splice(index, 1, board);
      } else {
        this.whiteboards.unshift(board);
      }
    },
    downloadState() {
      const state = JSON.stringify(this.serializeState(), null, 2);
      const blob = new Blob([state], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = `${this.whiteboardTitle || 'whiteboard'}.json`;
      anchor.click();
      URL.revokeObjectURL(url);
    },
    formatSnapshotLabel(snapshot) {
      const label = snapshot?.name || snapshot?.title || `Snapshot #${snapshot?.id}`;
      const createdAt = snapshot?.created_at || snapshot?.createdAt;
      if (!createdAt) {
        return label;
      }
      return `${label} – ${this.formatDate(createdAt)}`;
    },
    formatDate(date) {
      if (!date) {
        return '';
      }
      const parsed = new Date(date);
      if (Number.isNaN(parsed.getTime())) {
        return date;
      }
      return parsed.toLocaleString();
    },
    handleError(error, fallbackMessage) {
      console.error(error);
      if (error?.response?.data?.message) {
        this.error = error.response.data.message;
      } else if (error?.message) {
        this.error = error.message;
      } else {
        this.error = fallbackMessage;
      }
    },
    createNewWhiteboard() {
      this.selectedWhiteboardId = null;
      this.clearBoard();
      this.error = null;
      this.isSaving = false;
    }
  }
};
</script>

<style scoped>
.whiteboard-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.whiteboard-toolbar .form-label {
  display: block;
  margin-bottom: 0.25rem;
}

.whiteboard-toolbar .form-select,
.whiteboard-toolbar .form-control,
.whiteboard-toolbar .form-control-color {
  min-width: 120px;
}

.whiteboard-toolbar .tool-select {
  min-width: 110px;
}

.whiteboard-main {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
  flex-wrap: wrap;
}

.canvas-wrapper {
  position: relative;
  flex: 1 1 700px;
  min-height: 600px;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
}

.canvas-wrapper canvas {
  width: 100%;
  height: 100%;
  display: block;
  cursor: crosshair;
}

.sticky-note {
  position: absolute;
  width: 200px;
  min-height: 160px;
  box-shadow: 0 10px 15px rgba(55, 65, 81, 0.2);
  border-radius: 0.5rem;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.sticky-note textarea {
  background: transparent;
  border: none;
  resize: none;
  flex: 1;
  min-height: 120px;
  outline: none;
}

.sticky-note__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.5rem;
  cursor: move;
}

.board-image {
  position: absolute;
  background: #ffffff;
  border-radius: 0.5rem;
  box-shadow: 0 10px 15px rgba(31, 41, 55, 0.15);
  overflow: hidden;
  min-width: 150px;
}

.board-image__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(17, 24, 39, 0.75);
  color: #ffffff;
  padding: 0.25rem 0.5rem;
  cursor: move;
}

.board-image img {
  display: block;
  max-width: 100%;
  height: auto;
}

.snapshot-panel {
  flex: 0 0 260px;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  padding: 1rem;
  max-height: 640px;
  overflow-y: auto;
}

.snapshot-panel__section + .snapshot-panel__section {
  margin-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
  padding-top: 1rem;
}

.snapshot-list li + li,
.file-list li + li {
  margin-top: 0.5rem;
}

.snapshot-panel button.btn-link {
  text-decoration: none;
}

@media (max-width: 992px) {
  .snapshot-panel {
    flex: 1 1 100%;
    max-width: 100%;
  }
  .canvas-wrapper {
    flex: 1 1 100%;
  }
}
</style>
