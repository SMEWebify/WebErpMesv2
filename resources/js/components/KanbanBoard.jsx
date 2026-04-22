import React, { useState, useRef } from 'react';
import { formatQty } from '../utils';
import {
    DndContext,
    DragOverlay,
    PointerSensor,
    useDroppable,
    useSensor,
    useSensors,
    closestCorners,
} from '@dnd-kit/core';
import {
    SortableContext,
    verticalListSortingStrategy,
    useSortable,
    arrayMove,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

function TaskCard({ task }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: task.id,
    });

    return (
        <div
            ref={setNodeRef}
            style={{
                transform: CSS.Transform.toString(transform),
                transition,
                opacity: isDragging ? 0.4 : 1,
            }}
            {...attributes}
            {...listeners}
            className="card mb-2 bg-light"
        >
            <div className="card-body p-3">
                <a href={`/production/Task/Statu/Id/${task.id}`}>#{task.id}</a>
                {' - '}
                <span className="font-weight-bold">
                    <a href={`/orders/${task.order_lines.orders_id}`}>
                        {task.order_lines.order.code}
                    </a>
                </span>
                {` - ${task.label} - ${task.order_lines.delivery_date}`}
                <br />
                <span className="font-weight-bold">
                    {task.order_lines.label} || qty {formatQty(task.order_lines.qty)}
                </span>
                <br />
                <div className="float-right">
                    <div className="row">
                        <div className="col-4">
                            {task.service.picture && (
                                <img
                                    src={`/storage/images/methods/${task.service.picture}`}
                                    className="profile-user-img img-fluid img-circle"
                                    alt=""
                                />
                            )}
                        </div>
                        <div className="col-8">
                            <span className="font-weight-bold">Setting Time:</span>{' '}
                            {task.seting_time}
                            <br />
                            <span className="font-weight-bold">Unit Time:</span> {task.unit_time}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function KanbanColumn({ status, columnId }) {
    const { setNodeRef, isOver } = useDroppable({ id: columnId });

    return (
        <div className="col-12 col-lg-6 col-xl-3">
            <div className="card">
                <div className="card-header bg-blue">
                    <h5>{status.title}</h5>
                </div>
                <div
                    ref={setNodeRef}
                    className="card-body p-3"
                    style={{
                        minHeight: '80px',
                        backgroundColor: isOver ? 'rgba(0,0,0,0.04)' : undefined,
                        transition: 'background-color 0.15s ease',
                    }}
                >
                    <SortableContext
                        items={status.tasks.map((t) => t.id)}
                        strategy={verticalListSortingStrategy}
                    >
                        {status.tasks.map((task) => (
                            <TaskCard key={task.id} task={task} />
                        ))}
                    </SortableContext>
                    {status.tasks.length === 0 && (
                        <div className="p-4 text-center">
                            <span className="text-muted">No tasks yet</span>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function KanbanBoard({ initialData }) {
    const [statuses, setStatuses] = useState(initialData || []);
    const [activeTask, setActiveTask] = useState(null);

    // Always points to the latest statuses — safe to read in event handlers
    const latestStatuses = useRef(statuses);
    latestStatuses.current = statuses;

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } })
    );

    const columnId = (index) => `column-${index}`;

    function findStatusIndexByTaskId(taskId, list) {
        return list.findIndex((s) => s.tasks.some((t) => t.id === taskId));
    }

    // Resolves over.id to a status index — works for both task IDs and column IDs
    function resolveDestIndex(overId, list) {
        const byTask = findStatusIndexByTaskId(overId, list);
        if (byTask !== -1) return byTask;
        const match = String(overId).match(/^column-(\d+)$/);
        return match ? parseInt(match[1], 10) : -1;
    }

    function handleDragStart({ active }) {
        const idx = findStatusIndexByTaskId(active.id, latestStatuses.current);
        if (idx !== -1) {
            setActiveTask(latestStatuses.current[idx].tasks.find((t) => t.id === active.id));
        }
    }

    // Cross-column move: update state in real time so the column reacts visually
    function handleDragOver({ active, over }) {
        if (!over) return;

        const current = latestStatuses.current;
        const sourceIdx = findStatusIndexByTaskId(active.id, current);
        const destIdx = resolveDestIndex(over.id, current);

        if (sourceIdx === -1 || destIdx === -1 || sourceIdx === destIdx) return;

        setStatuses((prev) => {
            const next = prev.map((s) => ({ ...s, tasks: [...s.tasks] }));
            const task = next[sourceIdx].tasks.find((t) => t.id === active.id);
            next[sourceIdx].tasks = next[sourceIdx].tasks.filter((t) => t.id !== active.id);
            const overIdx = next[destIdx].tasks.findIndex((t) => t.id === over.id);
            if (overIdx >= 0) {
                next[destIdx].tasks.splice(overIdx, 0, task);
            } else {
                next[destIdx].tasks.push(task);
            }
            return next;
        });
    }

    function handleDragEnd({ active, over }) {
        setActiveTask(null);

        const current = latestStatuses.current;

        if (!over || active.id === over.id) {
            syncToServer(current);
            return;
        }

        const sourceIdx = findStatusIndexByTaskId(active.id, current);
        if (sourceIdx === -1) {
            syncToServer(current);
            return;
        }

        // Intra-column reorder (cross-column already handled by handleDragOver)
        const overInSameColumn = current[sourceIdx].tasks.some((t) => t.id === over.id);
        if (overInSameColumn) {
            const next = current.map((s) => ({ ...s, tasks: [...s.tasks] }));
            const oldIdx = next[sourceIdx].tasks.findIndex((t) => t.id === active.id);
            const newIdx = next[sourceIdx].tasks.findIndex((t) => t.id === over.id);
            next[sourceIdx].tasks = arrayMove(next[sourceIdx].tasks, oldIdx, newIdx);
            setStatuses(next);
            syncToServer(next);
        } else {
            syncToServer(current);
        }
    }

    function syncToServer(columns) {
        axios.put('/task/sync', { columns }).catch((err) => console.error(err));
    }

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCorners}
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDragEnd={handleDragEnd}
        >
            <div className="row">
                {statuses.map((status, index) => (
                    <KanbanColumn key={status.title} status={status} columnId={columnId(index)} />
                ))}
            </div>
            <DragOverlay>
                {activeTask && <TaskCard task={activeTask} />}
            </DragOverlay>
        </DndContext>
    );
}
