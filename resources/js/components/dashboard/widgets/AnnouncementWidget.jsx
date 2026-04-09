import React from 'react';
import WidgetCard from '../WidgetCard.jsx';

export default function AnnouncementWidget({ announcement, trans = {}, editMode, onRemove }) {
    return (
        <WidgetCard
            title={trans.announcement ?? 'Annonces'}
            icon="fa-envelope"
            theme="lightblue"
            empty={!announcement}
            emptyText={trans.no_announcement ?? 'Aucune annonce'}
            editMode={editMode}
            onRemove={onRemove}
        >
            {announcement && (
                <>
                    <h5 style={{ fontSize: '1rem', marginBottom: '0.5rem' }}>
                        <i className="fas fa-info mr-1" />
                        {announcement.title}
                    </h5>
                    <p style={{ fontSize: '0.85rem', color: '#495057', whiteSpace: 'pre-line', margin: 0 }}>
                        {announcement.comment}
                    </p>
                </>
            )}
        </WidgetCard>
    );
}
