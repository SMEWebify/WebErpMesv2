import { describe, expect, it } from 'vitest';
import { canDeleteFile, canReplaceFile, proposedRevision, versionCaption } from '../../lib/files/fileVersions.js';

describe('versionCaption', () => {
    it('shows nothing for a plain file', () => {
        expect(versionCaption({ version: 1, has_history: false })).toBe('');
    });

    it('shows the revision label of a plain file', () => {
        expect(versionCaption({ version: 1, has_history: false, revision_label: 'A' })).toBe('A');
    });

    it('shows the version number and label once there is a history', () => {
        expect(versionCaption({ version: 3, has_history: true, revision_label: 'C' }, 'v:version')).toBe('v3 · C');
        expect(versionCaption({ version: 1, has_history: true }, 'v:version')).toBe('v1');
    });
});

describe('canDeleteFile / canReplaceFile', () => {
    it('never deletes a versioned file', () => {
        expect(canDeleteFile({ has_history: true })).toBe(false);
        expect(canDeleteFile({ has_history: false })).toBe(true);
    });

    it('only replaces the latest version', () => {
        expect(canReplaceFile({ is_superseded: false })).toBe(true);
        expect(canReplaceFile({ is_superseded: true })).toBe(false);
        expect(canReplaceFile(null)).toBe(false);
    });
});

describe('proposedRevision', () => {
    it('proposes the item index, trimmed to the column size', () => {
        expect(proposedRevision(' B ')).toBe('B');
        expect(proposedRevision(null)).toBe('');
        expect(proposedRevision('x'.repeat(30))).toHaveLength(20);
    });
});
