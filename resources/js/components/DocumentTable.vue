<template>
    <div class="document-ged-table">
        <div class="p-3 border-bottom bg-light">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ translations.filters.typeLabel }}</label>
                    <select class="form-select" v-model="filters.type">
                        <option value="">{{ translations.filters.typePlaceholder }}</option>
                        <option v-for="type in availableTypes" :key="type" :value="type">
                            {{ type }}
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ translations.name }}</label>
                    <input
                        type="search"
                        class="form-control"
                        v-model="filters.search"
                        :placeholder="translations.filters.searchPlaceholder"
                    />
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ translations.filters.dateFrom }}</label>
                    <input type="date" class="form-control" v-model="filters.startDate" />
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ translations.filters.dateTo }}</label>
                    <input type="date" class="form-control" v-model="filters.endDate" />
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ translations.hashtags }}</label>
                    <input
                        type="text"
                        class="form-control"
                        list="document-hashtags"
                        v-model="filters.hashtag"
                        :placeholder="translations.filters.hashtagPlaceholder"
                    />
                    <datalist id="document-hashtags">
                        <option v-for="tag in availableHashtags" :key="tag" :value="tag">{{ tag }}</option>
                    </datalist>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ translations.filters.uploaderLabel }}</label>
                    <select class="form-select" v-model="filters.uploader">
                        <option value="">--</option>
                        <option v-for="uploader in availableUploaders" :key="uploader" :value="uploader">
                            {{ uploader }}
                        </option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end align-items-end">
                    <button class="btn btn-outline-secondary" type="button" @click="resetFilters">
                        <i class="fas fa-undo me-2"></i>{{ translations.filters.reset }}
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th
                            v-for="(column, index) in columns"
                            :key="column.key"
                            scope="col"
                            class="text-nowrap"
                            :class="{ 'is-sortable': column.sortable, sorted: sortState.key === column.key }"
                            draggable="true"
                            @dragstart="onDragStart(index)"
                            @dragover.prevent
                            @drop="onDrop(index)"
                            @click="column.sortable && toggleSort(column.key)"
                        >
                            <span>{{ column.label }}</span>
                            <span v-if="column.sortable" class="ms-1 sort-indicator">
                                <i
                                    v-if="sortState.key === column.key"
                                    :class="sortState.direction === 'asc' ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"
                                ></i>
                                <i v-else class="fas fa-sort"></i>
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="document in sortedDocuments" :key="document.id">
                        <td
                            v-for="column in columns"
                            :key="`${document.id}-${column.key}`"
                            class="align-top"
                            :class="column.key === 'type' ? 'text-nowrap' : ''"
                        >
                            <template v-if="column.key === 'name'">
                                <span class="fw-semibold">{{ document.name }}</span>
                                <small v-if="document.original_file_name && document.original_file_name !== document.name" class="d-block text-muted">
                                    {{ document.original_file_name }}
                                </small>
                            </template>
                            <template v-else-if="column.key === 'type'">
                                {{ document.type || '—' }}
                            </template>
                            <template v-else-if="column.key === 'formatted_size'">
                                {{ document.formatted_size }}
                            </template>
                            <template v-else-if="column.key === 'created_at'">
                                <span class="d-block">{{ document.created_at_human || '—' }}</span>
                                <small class="text-muted" v-if="document.created_at">{{ formatDate(document.created_at) }}</small>
                            </template>
                            <template v-else-if="column.key === 'updated_at'">
                                <span class="d-block">{{ document.updated_at_human || '—' }}</span>
                                <small class="text-muted" v-if="document.updated_at">{{ formatDate(document.updated_at) }}</small>
                            </template>
                            <template v-else-if="column.key === 'hashtags'">
                                <span
                                    v-if="document.hashtags.length"
                                    class="badge bg-secondary me-1 mb-1"
                                    v-for="tag in document.hashtags"
                                    :key="`${document.id}-${tag}`"
                                >
                                    #{{ tag }}
                                </span>
                                <span v-else>—</span>
                            </template>
                            <template v-else-if="column.key === 'uploaded_by'">
                                {{ document.uploaded_by || '—' }}
                            </template>
                            <template v-else-if="column.key === 'linked_entities'">
                                <ul class="list-unstyled mb-0">
                                    <li v-for="(entity, entityIndex) in document.linked_entities" :key="`${document.id}-entity-${entityIndex}`">
                                        <i class="fas fa-link me-2 text-muted"></i>{{ entity }}
                                    </li>
                                    <li v-if="!document.linked_entities.length" class="text-muted">—</li>
                                </ul>
                            </template>
                            <template v-else>
                                {{ document[column.key] ?? '—' }}
                            </template>
                        </td>
                    </tr>
                    <tr v-if="!sortedDocuments.length">
                        <td :colspan="columns.length" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-3 d-block"></i>
                            {{ translations.filters.noResult }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    initialDocuments: {
        type: Array,
        default: () => [],
    },
    translations: {
        type: Object,
        default: () => ({}),
    },
});

const documents = ref(
    props.initialDocuments.map((doc) => ({
        ...doc,
        hashtags: Array.isArray(doc.hashtags) ? doc.hashtags : [],
        linked_entities: Array.isArray(doc.linked_entities) ? doc.linked_entities : [],
    }))
);

const filters = reactive({
    type: '',
    search: '',
    startDate: '',
    endDate: '',
    hashtag: '',
    uploader: '',
});

const columns = ref([
    { key: 'name', label: props.translations.name, sortable: true },
    { key: 'type', label: props.translations.type, sortable: true },
    { key: 'formatted_size', label: props.translations.size, sortable: true },
    { key: 'created_at', label: props.translations.uploaded_at, sortable: true },
    { key: 'updated_at', label: props.translations.updated_at, sortable: true },
    { key: 'hashtags', label: props.translations.hashtags, sortable: true },
    { key: 'uploaded_by', label: props.translations.uploaded_by, sortable: true },
    { key: 'linked_entities', label: props.translations.linked_entities, sortable: false },
]);

const sortState = reactive({
    key: 'created_at',
    direction: 'desc',
});

const dragState = ref(null);

const availableTypes = computed(() => {
    const types = new Set(documents.value.map((doc) => doc.type).filter(Boolean));
    return Array.from(types).sort();
});

const availableHashtags = computed(() => {
    const hashtags = new Set();
    documents.value.forEach((doc) => {
        doc.hashtags.forEach((tag) => hashtags.add(`#${tag}`));
    });
    return Array.from(hashtags).sort((a, b) => a.localeCompare(b));
});

const availableUploaders = computed(() => {
    const uploaders = new Set(documents.value.map((doc) => doc.uploaded_by).filter(Boolean));
    return Array.from(uploaders).sort((a, b) => a.localeCompare(b));
});

const filteredDocuments = computed(() =>
    documents.value.filter((doc) => {
        if (filters.type && doc.type !== filters.type) {
            return false;
        }

        if (filters.uploader && doc.uploaded_by !== filters.uploader) {
            return false;
        }

        if (filters.search) {
            const haystack = [doc.name, doc.original_file_name, doc.comment]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            if (!haystack.includes(filters.search.toLowerCase())) {
                return false;
            }
        }

        if (filters.hashtag) {
            const normalizedFilter = filters.hashtag.replace(/^#/, '').toLowerCase();
            const hasTag = doc.hashtags.some((tag) => tag.toLowerCase().includes(normalizedFilter));
            if (!hasTag) {
                return false;
            }
        }

        if (filters.startDate && doc.created_at) {
            const createdDate = new Date(doc.created_at);
            const start = new Date(filters.startDate);
            if (createdDate < start) {
                return false;
            }
        }

        if (filters.endDate && doc.created_at) {
            const createdDate = new Date(doc.created_at);
            const end = new Date(filters.endDate);
            end.setHours(23, 59, 59, 999);
            if (createdDate > end) {
                return false;
            }
        }

        return true;
    })
);

const sortedDocuments = computed(() => {
    const docs = [...filteredDocuments.value];
    const { key, direction } = sortState;

    docs.sort((a, b) => {
        const aValue = getSortableValue(a, key);
        const bValue = getSortableValue(b, key);

        if (aValue === bValue) {
            return 0;
        }

        if (aValue === null || aValue === undefined) {
            return direction === 'asc' ? 1 : -1;
        }

        if (bValue === null || bValue === undefined) {
            return direction === 'asc' ? -1 : 1;
        }

        if (aValue > bValue) {
            return direction === 'asc' ? 1 : -1;
        }

        if (aValue < bValue) {
            return direction === 'asc' ? -1 : 1;
        }

        return 0;
    });

    return docs;
});

function getSortableValue(document, key) {
    switch (key) {
        case 'formatted_size':
            return document.size ?? 0;
        case 'hashtags':
            return document.hashtags.join(' ').toLowerCase();
        case 'created_at':
        case 'updated_at':
            return document[key] ? new Date(document[key]).getTime() : 0;
        default: {
            const value = document[key];
            if (value === null || value === undefined) {
                return '';
            }
            if (typeof value === 'string') {
                return value.toLowerCase();
            }
            return value;
        }
    }
}

function toggleSort(columnKey) {
    if (sortState.key === columnKey) {
        sortState.direction = sortState.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sortState.key = columnKey;
        sortState.direction = 'asc';
    }
}

function onDragStart(index) {
    dragState.value = index;
}

function onDrop(index) {
    if (dragState.value === null || dragState.value === index) {
        dragState.value = null;
        return;
    }

    const updated = [...columns.value];
    const [moved] = updated.splice(dragState.value, 1);
    updated.splice(index, 0, moved);
    columns.value = updated;
    dragState.value = null;
}

function resetFilters() {
    filters.type = '';
    filters.search = '';
    filters.startDate = '';
    filters.endDate = '';
    filters.hashtag = '';
    filters.uploader = '';
}

function formatDate(isoString) {
    if (!isoString) {
        return '';
    }
    const date = new Date(isoString);
    return date.toLocaleString();
}
</script>

<style scoped>
.document-ged-table {
    min-height: 420px;
}

.document-ged-table .is-sortable {
    cursor: pointer;
    user-select: none;
}

.document-ged-table th.is-sortable:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.document-ged-table th {
    transition: background-color 0.2s ease-in-out;
}

.document-ged-table .sort-indicator {
    color: #6c757d;
    font-size: 0.85rem;
}

.document-ged-table .sorted {
    background-color: rgba(13, 110, 253, 0.08);
}

.document-ged-table ul {
    max-height: 120px;
    overflow-y: auto;
}
</style>
