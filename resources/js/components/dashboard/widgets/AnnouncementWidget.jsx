import React from 'react';
import WidgetCard from '../WidgetCard.jsx';

export default function AnnouncementWidget({ announcement, trans = {}, editMode, onRemove }) {
    return (
        <WidgetCard
            title={trans.announcement ?? 'Annonces'}
            icon="fa-envelope"
            theme="sky"
            empty={!announcement}
            emptyText={trans.no_announcement ?? 'Aucune annonce'}
            editMode={editMode}
            onRemove={onRemove}
        >
            {announcement && (
                <>
                    <h5 style={{ fontSize: '1rem', marginBottom: '0.5rem' }}>
                        <i className="fas fa-info me-1" />
                        {announcement.title}
                    </h5>
                    <p style={{ fontSize: '0.85rem', color: 'var(--bs-body-color)', whiteSpace: 'pre-line', margin: 0 }}>
                        {announcement.comment}
                    </p>
                </>
            )}
        </WidgetCard>
    );
}
