// ─── CSS — ordre obligatoire ──────────────────────────────────────────────────
import '@univerjs/design/lib/index.css';
import '@univerjs/ui/lib/index.css';
import '@univerjs/docs-ui/lib/index.css';
import '@univerjs/sheets-ui/lib/index.css';
import '@univerjs/sheets-formula-ui/lib/index.css';
import '@univerjs/sheets-numfmt-ui/lib/index.css';

// ─── Core ─────────────────────────────────────────────────────────────────────
import {
    Univer,
    LocaleType,
    mergeLocales,
    UniverInstanceType,
    IUniverInstanceService,
    ICommandService,
} from '@univerjs/core';

// ─── Locales ──────────────────────────────────────────────────────────────────
import DesignFrFR        from '@univerjs/design/lib/locale/fr-FR';
import UIFrFR            from '@univerjs/ui/lib/locale/fr-FR';
import DocsFrFR          from '@univerjs/docs-ui/lib/locale/fr-FR';
import SheetsFrFR        from '@univerjs/sheets/lib/locale/fr-FR';
import SheetsUIFrFR      from '@univerjs/sheets-ui/lib/locale/fr-FR';
import SheetsFormulaFrFR from '@univerjs/sheets-formula-ui/lib/locale/fr-FR';
import SheetsNumfmtFrFR  from '@univerjs/sheets-numfmt-ui/lib/locale/fr-FR';

// ─── Plugins ──────────────────────────────────────────────────────────────────
import { UniverRenderEnginePlugin }      from '@univerjs/engine-render';
import { UniverFormulaEnginePlugin }     from '@univerjs/engine-formula';
import { UniverUIPlugin }                from '@univerjs/ui';
import { UniverDocsPlugin }              from '@univerjs/docs';
import { UniverDocsUIPlugin }            from '@univerjs/docs-ui';
import { UniverSheetsPlugin }            from '@univerjs/sheets';
import { UniverSheetsUIPlugin }          from '@univerjs/sheets-ui';
import { UniverSheetsFormulaPlugin }     from '@univerjs/sheets-formula';
import { UniverSheetsFormulaUIPlugin }   from '@univerjs/sheets-formula-ui';
import { UniverSheetsNumfmtPlugin }      from '@univerjs/sheets-numfmt';
import { UniverSheetsNumfmtUIPlugin }    from '@univerjs/sheets-numfmt-ui';

// ─── Facades (optionnel mais recommandé pour l'API save/load) ─────────────────

import { WemFormulaPlugin } from './plugins/WemFormulaPlugin';

// ─── Init ─────────────────────────────────────────────────────────────────────
const config = window.WEM_SPREADSHEET;

if (!config || !document.getElementById('univer-container')) {
    console.error('WEM Spreadsheet: config ou container manquant');
} else {
    const univer = new Univer({
    locale: LocaleType.FR_FR,
    locales: {
        [LocaleType.FR_FR]: mergeLocales(
            DesignFrFR,
            UIFrFR,
            DocsFrFR,
            SheetsFrFR,
            SheetsUIFrFR,
            SheetsFormulaFrFR,
            SheetsNumfmtFrFR,
        ),
    },
});

    // ─── Ordre d'enregistrement obligatoire ───────────────────────────────────
    univer.registerPlugin(UniverRenderEnginePlugin);
    univer.registerPlugin(UniverFormulaEnginePlugin);

    univer.registerPlugin(UniverUIPlugin, {
        container: 'univer-container',
    });

    univer.registerPlugin(UniverDocsPlugin);
    univer.registerPlugin(UniverDocsUIPlugin);

    univer.registerPlugin(UniverSheetsPlugin);
    univer.registerPlugin(UniverSheetsUIPlugin);
    univer.registerPlugin(UniverSheetsFormulaPlugin);
    univer.registerPlugin(UniverSheetsFormulaUIPlugin);
    univer.registerPlugin(UniverSheetsNumfmtPlugin);
    univer.registerPlugin(UniverSheetsNumfmtUIPlugin);

    // ─── Charger les données existantes ───────────────────────────────────────
    const workbookData = {
        id: `spreadsheet-${config.id}`,
        name: config.name,
        sheetOrder: config.sheets.map((s, i) => s?.data?.id || `sheet-${i + 1}`),
        sheets: config.sheets.reduce((carry, sheet, index) => {
            const sheetId = sheet?.data?.id || `sheet-${index + 1}`;
            carry[sheetId] = {
                id: sheetId,
                name: sheet.name || `Feuille${index + 1}`,
                cellData: {},
                ...(sheet.data || {}),
            };
            return carry;
        }, {}),
    };

    univer.createUnit(UniverInstanceType.UNIVER_SHEET, workbookData);

    // ─── Plugin formules WEM ──────────────────────────────────────────────────
    const wemPlugin = new WemFormulaPlugin({ dataApiBase: config.dataApiBase });
    wemPlugin.register(univer);

    // ─── Auto-save ────────────────────────────────────────────────────────────
    const saveStatus = document.getElementById('save-status');
    let saveTimer = null;

    const setStatus = (text) => {
        if (saveStatus) saveStatus.textContent = text;
    };

    const saveWorkbook = async () => {
        setStatus('Enregistrement…');

        const instanceService = univer.__getInjector().get(IUniverInstanceService);
        const workbook = instanceService.getCurrentUnitForType(UniverInstanceType.UNIVER_SHEET);
        const snapshot = workbook?.save() ?? null;

        const res = await fetch(config.saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({ snapshot }),
        });

        setStatus(res.ok ? 'Enregistré ✓' : 'Erreur de sauvegarde');
    };

    const debounceSave = () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            saveWorkbook().catch(() => setStatus('Erreur de sauvegarde'));
        }, 2000);
    };

    // Écoute des changements via ICommandService
    const commandService = univer.__getInjector().get(ICommandService);
    if (commandService?.onCommandExecuted) {
        commandService.onCommandExecuted(() => debounceSave());
    }
}