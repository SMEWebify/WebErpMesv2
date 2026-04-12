import React, { useState, useCallback, useRef, useEffect } from 'react';
import ReactFlow, {
    ReactFlowProvider,
    addEdge,
    useNodesState,
    useEdgesState,
    Controls,
    Background,
    MiniMap,
    Handle,
    Position,
    getBezierPath,
    MarkerType,
} from 'reactflow';
import 'reactflow/dist/style.css';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
        ...options,
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.message || `HTTP ${res.status}`);
    return json;
}

function urlId(template, id) {
    return template.replace('__ID__', id);
}

// ---------------------------------------------------------------------------
// Node types definition
// ---------------------------------------------------------------------------

const NODE_TYPES_META = [
    { type: 'process',   label: 'Processus',    color: '#3b82f6', shape: 'rect',        icon: 'fas fa-cog' },
    { type: 'decision',  label: 'Décision',     color: '#f59e0b', shape: 'diamond',     icon: 'fas fa-code-branch' },
    { type: 'io',        label: 'Entrée/Sortie', color: '#10b981', shape: 'parallelogram', icon: 'fas fa-exchange-alt' },
    { type: 'trigger',   label: 'Déclencheur',  color: '#8b5cf6', shape: 'rounded',     icon: 'fas fa-play-circle' },
    { type: 'document',  label: 'Document',     color: '#6b7280', shape: 'document',    icon: 'fas fa-file-alt' },
];

// ---------------------------------------------------------------------------
// Custom Nodes
// ---------------------------------------------------------------------------

function NodeWrapper({ data, selected, children, style = {} }) {
    return (
        <div style={{
            padding: '8px 14px',
            borderRadius: 6,
            border: `2px solid ${selected ? '#1d4ed8' : data.color ?? '#3b82f6'}`,
            background: '#fff',
            boxShadow: selected ? '0 0 0 3px rgba(59,130,246,0.3)' : '0 1px 4px rgba(0,0,0,0.12)',
            minWidth: 120,
            textAlign: 'center',
            fontSize: 12,
            cursor: 'default',
            ...style,
        }}>
            <Handle type="target" position={Position.Left}  style={{ background: data.color ?? '#3b82f6' }} />
            <Handle type="target" position={Position.Top}   style={{ background: data.color ?? '#3b82f6' }} />
            {children}
            <Handle type="source" position={Position.Right}  style={{ background: data.color ?? '#3b82f6' }} />
            <Handle type="source" position={Position.Bottom} style={{ background: data.color ?? '#3b82f6' }} />
        </div>
    );
}

function ProcessNode({ data, selected }) {
    return (
        <NodeWrapper data={data} selected={selected}>
            <div style={{ fontWeight: 700, color: data.color ?? '#3b82f6', marginBottom: 2 }}>
                <i className="fas fa-cog mr-1" />{data.label}
            </div>
            {data.pilot    && <div style={{ color: '#6b7280', fontSize: 11 }}>Pilote : {data.pilot}</div>}
            {data.kpi      && <div style={{ color: '#6b7280', fontSize: 11 }}>KPI : {data.kpi}</div>}
        </NodeWrapper>
    );
}

function DecisionNode({ data, selected }) {
    return (
        <div style={{ position: 'relative', width: 110, height: 70 }}>
            <Handle type="target" position={Position.Left}   style={{ top: '50%', background: data.color ?? '#f59e0b' }} />
            <Handle type="target" position={Position.Top}    style={{ left: '50%', background: data.color ?? '#f59e0b' }} />
            <Handle type="source" position={Position.Right}  style={{ top: '50%', background: data.color ?? '#f59e0b' }} />
            <Handle type="source" position={Position.Bottom} style={{ left: '50%', background: data.color ?? '#f59e0b' }} />
            <svg width="110" height="70" style={{ position: 'absolute', top: 0, left: 0 }}>
                <polygon
                    points="55,4 106,35 55,66 4,35"
                    fill="#fff"
                    stroke={selected ? '#1d4ed8' : (data.color ?? '#f59e0b')}
                    strokeWidth={selected ? 2.5 : 2}
                />
            </svg>
            <div style={{
                position: 'absolute', top: '50%', left: '50%',
                transform: 'translate(-50%,-50%)',
                fontSize: 11, fontWeight: 700, color: data.color ?? '#f59e0b',
                textAlign: 'center', width: 80,
            }}>
                {data.label}
            </div>
        </div>
    );
}

function IONode({ data, selected }) {
    return (
        <NodeWrapper data={data} selected={selected} style={{
            background: `${data.color ?? '#10b981'}15`,
            borderColor: selected ? '#1d4ed8' : (data.color ?? '#10b981'),
            borderStyle: 'dashed',
        }}>
            <div style={{ fontWeight: 700, color: data.color ?? '#10b981' }}>
                <i className="fas fa-exchange-alt mr-1" />{data.label}
            </div>
        </NodeWrapper>
    );
}

function TriggerNode({ data, selected }) {
    return (
        <NodeWrapper data={data} selected={selected} style={{
            borderRadius: 30,
            background: `${data.color ?? '#8b5cf6'}15`,
            borderColor: selected ? '#1d4ed8' : (data.color ?? '#8b5cf6'),
        }}>
            <div style={{ fontWeight: 700, color: data.color ?? '#8b5cf6' }}>
                <i className="fas fa-play-circle mr-1" />{data.label}
            </div>
        </NodeWrapper>
    );
}

function DocumentNode({ data, selected }) {
    return (
        <NodeWrapper data={data} selected={selected} style={{ borderStyle: 'dotted' }}>
            <div style={{ fontWeight: 600, color: data.color ?? '#6b7280' }}>
                <i className="fas fa-file-alt mr-1" />{data.label}
            </div>
        </NodeWrapper>
    );
}

const NODE_COMPONENTS = {
    process:  ProcessNode,
    decision: DecisionNode,
    io:       IONode,
    trigger:  TriggerNode,
    document: DocumentNode,
};

// ---------------------------------------------------------------------------
// Palette (left sidebar)
// ---------------------------------------------------------------------------

function Palette() {
    const onDragStart = (e, type) => {
        e.dataTransfer.setData('application/reactflow', type);
        e.dataTransfer.effectAllowed = 'move';
    };

    return (
        <div style={{
            width: 160, background: '#f8fafc', borderRight: '1px solid #e2e8f0',
            padding: '12px 8px', display: 'flex', flexDirection: 'column', gap: 8,
        }}>
            <div style={{ fontSize: 11, fontWeight: 700, color: '#64748b', textTransform: 'uppercase', marginBottom: 4 }}>
                Palette
            </div>
            {NODE_TYPES_META.map(meta => (
                <div
                    key={meta.type}
                    draggable
                    onDragStart={e => onDragStart(e, meta.type)}
                    style={{
                        padding: '7px 10px',
                        borderRadius: 6,
                        border: `2px solid ${meta.color}`,
                        background: `${meta.color}18`,
                        color: meta.color,
                        fontWeight: 600,
                        fontSize: 12,
                        cursor: 'grab',
                        userSelect: 'none',
                    }}
                >
                    <i className={`${meta.icon} mr-1`} />{meta.label}
                </div>
            ))}
            <div style={{ fontSize: 10, color: '#94a3b8', marginTop: 8 }}>
                Glissez un élément sur le canvas
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Properties panel (right sidebar)
// ---------------------------------------------------------------------------

function PropertiesPanel({ node, onChange }) {
    if (!node) {
        return (
            <div style={{ width: 200, background: '#f8fafc', borderLeft: '1px solid #e2e8f0', padding: 16 }}>
                <div style={{ fontSize: 11, color: '#94a3b8' }}>
                    Sélectionnez un nœud pour éditer ses propriétés.
                </div>
            </div>
        );
    }

    const meta = NODE_TYPES_META.find(m => m.type === node.type);
    const data = node.data ?? {};

    const set = (key) => (e) => onChange(node.id, { ...data, [key]: e.target.value });

    return (
        <div style={{ width: 200, background: '#f8fafc', borderLeft: '1px solid #e2e8f0', padding: 16, overflowY: 'auto' }}>
            <div style={{ fontSize: 11, fontWeight: 700, color: meta?.color ?? '#3b82f6', textTransform: 'uppercase', marginBottom: 12 }}>
                <i className={`${meta?.icon} mr-1`} />{meta?.label ?? node.type}
            </div>

            <div style={{ marginBottom: 10 }}>
                <label style={{ fontSize: 11, fontWeight: 600, color: '#475569' }}>Libellé</label>
                <input
                    className="form-control form-control-sm mt-1"
                    value={data.label ?? ''}
                    onChange={set('label')}
                />
            </div>

            {node.type === 'process' && (
                <>
                    <div style={{ marginBottom: 10 }}>
                        <label style={{ fontSize: 11, fontWeight: 600, color: '#475569' }}>Pilote</label>
                        <input className="form-control form-control-sm mt-1" value={data.pilot ?? ''} onChange={set('pilot')} />
                    </div>
                    <div style={{ marginBottom: 10 }}>
                        <label style={{ fontSize: 11, fontWeight: 600, color: '#475569' }}>KPI</label>
                        <input className="form-control form-control-sm mt-1" value={data.kpi ?? ''} onChange={set('kpi')} />
                    </div>
                    <div style={{ marginBottom: 10 }}>
                        <label style={{ fontSize: 11, fontWeight: 600, color: '#475569' }}>Entrée</label>
                        <input className="form-control form-control-sm mt-1" value={data.input ?? ''} onChange={set('input')} />
                    </div>
                    <div style={{ marginBottom: 10 }}>
                        <label style={{ fontSize: 11, fontWeight: 600, color: '#475569' }}>Sortie</label>
                        <input className="form-control form-control-sm mt-1" value={data.output ?? ''} onChange={set('output')} />
                    </div>
                </>
            )}

            <div style={{ marginBottom: 10 }}>
                <label style={{ fontSize: 11, fontWeight: 600, color: '#475569' }}>Couleur</label>
                <input type="color" className="form-control form-control-sm mt-1" style={{ height: 32 }}
                    value={data.color ?? (meta?.color ?? '#3b82f6')}
                    onChange={set('color')} />
            </div>

            {data.input && (
                <div style={{ marginTop: 12, padding: '8px', background: '#fff', borderRadius: 4, border: '1px solid #e2e8f0', fontSize: 11 }}>
                    <div><strong>Entrée :</strong> {data.input}</div>
                    <div><strong>Sortie :</strong> {data.output}</div>
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Diagram list (left drawer when no diagram open)
// ---------------------------------------------------------------------------

function DiagramList({ diagrams, onSelect, onCreate, onDelete, loading }) {
    const [name, setName] = useState('');
    const [desc, setDesc] = useState('');

    const handleCreate = (e) => {
        e.preventDefault();
        if (!name.trim()) return;
        onCreate(name.trim(), desc.trim());
        setName(''); setDesc('');
    };

    return (
        <div className="card card-primary card-outline">
            <div className="card-header">
                <h3 className="card-title"><i className="fas fa-sitemap mr-1" />Mes diagrammes</h3>
            </div>
            <div className="card-body p-0">
                {loading ? (
                    <div className="p-3 text-muted">Chargement...</div>
                ) : diagrams.length === 0 ? (
                    <div className="p-3 text-muted">Aucun diagramme</div>
                ) : (
                    <ul className="list-group list-group-flush">
                        {diagrams.map(d => (
                            <li key={d.id} className="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div className="font-weight-bold">{d.name}</div>
                                    {d.description && <div className="text-muted small">{d.description}</div>}
                                    <div className="text-muted small">{d.creator?.name ?? ''}</div>
                                </div>
                                <div className="btn-group btn-group-sm">
                                    <button className="btn btn-outline-primary" onClick={() => onSelect(d.id)}>
                                        <i className="fas fa-edit" />
                                    </button>
                                    <button className="btn btn-outline-danger" onClick={() => onDelete(d.id)}>
                                        <i className="fas fa-trash" />
                                    </button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
            <div className="card-footer">
                <form onSubmit={handleCreate}>
                    <div className="form-group mb-2">
                        <input
                            className="form-control form-control-sm"
                            placeholder="Nom du diagramme"
                            value={name}
                            onChange={e => setName(e.target.value)}
                            required
                        />
                    </div>
                    <div className="form-group mb-2">
                        <input
                            className="form-control form-control-sm"
                            placeholder="Description (optionnel)"
                            value={desc}
                            onChange={e => setDesc(e.target.value)}
                        />
                    </div>
                    <button className="btn btn-primary btn-sm btn-block" type="submit">
                        <i className="fas fa-plus mr-1" />Nouveau diagramme
                    </button>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Canvas editor
// ---------------------------------------------------------------------------

let nodeId = 1000;
const newId = () => `node_${++nodeId}`;

function DiagramEditor({ diagram, endpoints, onSaved, onClose }) {
    const reactFlowWrapper = useRef(null);
    const [rfInstance, setRfInstance] = useState(null);
    const [nodes, setNodes, onNodesChange] = useNodesState(diagram.nodes ?? []);
    const [edges, setEdges, onEdgesChange] = useEdgesState(diagram.edges ?? []);
    const [selectedNode, setSelectedNode] = useState(null);
    const [saving, setSaving] = useState(false);
    const [dirty, setDirty] = useState(false);

    const onConnect = useCallback((params) => {
        setEdges(eds => addEdge({
            ...params,
            markerEnd: { type: MarkerType.ArrowClosed },
            style: { strokeWidth: 2 },
            animated: false,
        }, eds));
        setDirty(true);
    }, [setEdges]);

    const onDrop = useCallback((e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('application/reactflow');
        if (!type || !reactFlowWrapper.current || !rfInstance) return;

        const bounds = reactFlowWrapper.current.getBoundingClientRect();
        const position = rfInstance.screenToFlowPosition({
            x: e.clientX - bounds.left,
            y: e.clientY - bounds.top,
        });

        const meta = NODE_TYPES_META.find(m => m.type === type);
        const newNode = {
            id: newId(),
            type,
            position,
            data: { label: meta?.label ?? type, color: meta?.color },
        };
        setNodes(nds => [...nds, newNode]);
        setDirty(true);
    }, [rfInstance, setNodes]);

    const onDragOver = useCallback((e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }, []);

    const onNodeClick = useCallback((_, node) => setSelectedNode(node), []);
    const onPaneClick  = useCallback(() => setSelectedNode(null), []);

    const handleNodeDataChange = useCallback((nodeId, newData) => {
        setNodes(nds => nds.map(n => n.id === nodeId ? { ...n, data: newData } : n));
        setSelectedNode(prev => prev?.id === nodeId ? { ...prev, data: newData } : prev);
        setDirty(true);
    }, [setNodes]);

    const handleSave = async () => {
        setSaving(true);
        try {
            await apiFetch(urlId(endpoints.update, diagram.id), {
                method: 'PUT',
                body: JSON.stringify({ nodes, edges }),
            });
            setDirty(false);
            onSaved();
        } finally {
            setSaving(false);
        }
    };

    return (
        <div style={{ display: 'flex', flexDirection: 'column', height: 'calc(100vh - 180px)', background: '#fff', borderRadius: 8, boxShadow: '0 1px 8px rgba(0,0,0,0.1)' }}>
            {/* Toolbar */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, padding: '8px 12px', borderBottom: '1px solid #e2e8f0', background: '#f8fafc' }}>
                <button className="btn btn-sm btn-outline-secondary" onClick={onClose}>
                    <i className="fas fa-arrow-left mr-1" />Retour
                </button>
                <strong style={{ fontSize: 14 }}>{diagram.name}</strong>
                {dirty && <span className="badge badge-warning ml-1">Non sauvegardé</span>}
                <div style={{ marginLeft: 'auto', display: 'flex', gap: 6 }}>
                    <button className="btn btn-sm btn-success" onClick={handleSave} disabled={saving || !dirty}>
                        <i className="fas fa-save mr-1" />{saving ? 'Sauvegarde...' : 'Sauvegarder'}
                    </button>
                </div>
            </div>

            {/* Main area */}
            <div style={{ display: 'flex', flex: 1, overflow: 'hidden' }}>
                <Palette />

                <div ref={reactFlowWrapper} style={{ flex: 1 }}>
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        onNodesChange={(changes) => { onNodesChange(changes); setDirty(true); }}
                        onEdgesChange={(changes) => { onEdgesChange(changes); setDirty(true); }}
                        onConnect={onConnect}
                        onInit={setRfInstance}
                        onDrop={onDrop}
                        onDragOver={onDragOver}
                        onNodeClick={onNodeClick}
                        onPaneClick={onPaneClick}
                        nodeTypes={NODE_COMPONENTS}
                        fitView
                        deleteKeyCode="Delete"
                    >
                        <Controls />
                        <MiniMap nodeStrokeWidth={3} zoomable pannable />
                        <Background variant="dots" gap={16} size={1} color="#cbd5e1" />
                    </ReactFlow>
                </div>

                <PropertiesPanel node={selectedNode} onChange={handleNodeDataChange} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root App
// ---------------------------------------------------------------------------

export default function ProcessDiagramApp({ endpoints }) {
    const [diagrams, setDiagrams]         = useState([]);
    const [activeDiagram, setActiveDiagram] = useState(null);
    const [loading, setLoading]           = useState(true);
    const [error, setError]               = useState(null);

    const loadDiagrams = useCallback(async () => {
        try {
            const data = await apiFetch(endpoints.index);
            setDiagrams(data);
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    }, [endpoints.index]);

    useEffect(() => { loadDiagrams(); }, [loadDiagrams]);

    const handleCreate = async (name, description) => {
        try {
            const created = await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify({ name, description, nodes: [], edges: [] }),
            });
            await loadDiagrams();
            setActiveDiagram(created);
        } catch (e) {
            setError(e.message);
        }
    };

    const handleSelect = async (id) => {
        try {
            const data = await apiFetch(urlId(endpoints.show, id));
            setActiveDiagram(data);
        } catch (e) {
            setError(e.message);
        }
    };

    const handleDelete = async (id) => {
        if (!confirm('Supprimer ce diagramme ?')) return;
        try {
            await apiFetch(urlId(endpoints.delete, id), { method: 'DELETE' });
            await loadDiagrams();
            if (activeDiagram?.id === id) setActiveDiagram(null);
        } catch (e) {
            setError(e.message);
        }
    };

    return (
        <ReactFlowProvider>
            <div>
                {error && (
                    <div className="alert alert-danger alert-dismissible">
                        {error}
                        <button type="button" className="close" onClick={() => setError(null)}>&times;</button>
                    </div>
                )}

                {activeDiagram ? (
                    <DiagramEditor
                        diagram={activeDiagram}
                        endpoints={endpoints}
                        onSaved={loadDiagrams}
                        onClose={() => setActiveDiagram(null)}
                    />
                ) : (
                    <div className="row">
                        <div className="col-lg-5">
                            <DiagramList
                                diagrams={diagrams}
                                onSelect={handleSelect}
                                onCreate={handleCreate}
                                onDelete={handleDelete}
                                loading={loading}
                            />
                        </div>
                        <div className="col-lg-7">
                            <div className="card card-outline card-secondary">
                                <div className="card-body text-muted text-center" style={{ padding: '60px 20px' }}>
                                    <i className="fas fa-sitemap fa-3x mb-3" style={{ color: '#cbd5e1' }} />
                                    <div>Créez ou sélectionnez un diagramme pour commencer.</div>
                                    <div className="mt-2 small">
                                        Glissez-déposez des nœuds depuis la palette, connectez-les, et sauvegardez.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </ReactFlowProvider>
    );
}
