import '@univerjs/ui/lib/index.css';
import { Univer, LocaleType, merge } from '@univerjs/core';
import { UniverSheetsPlugin } from '@univerjs/sheets';
import { UniverSheetsUIPlugin } from '@univerjs/sheets-ui';
import * as UniverSheetsFormula from '@univerjs/sheets-formula';
import { WemFormulaPlugin } from './plugins/WemFormulaPlugin';

const config = window.WEM_SPREADSHEET;

if (config && document.getElementById('univer-container')) {
    const registerPluginIfAvailable = (univer, plugin, options = undefined) => {
        if (!plugin) {
            return;
        }

        if (options === undefined) {
            univer.registerPlugin(plugin);
            return;
        }

        univer.registerPlugin(plugin, options);
    };

    const formulaPlugin =
        UniverSheetsFormula.UniverFormulaEnginePlugin || UniverSheetsFormula.UniverSheetsFormulaPlugin;

    const univer = new Univer({
        locale: LocaleType.FR_FR,
        locales: {},
    });

    registerPluginIfAvailable(univer, UniverSheetsPlugin);
    registerPluginIfAvailable(univer, formulaPlugin);
    registerPluginIfAvailable(univer, UniverSheetsUIPlugin, {
        container: 'univer-container',
    });

    const workbookData = {
        id: `spreadsheet-${config.id}`,
        name: config.name,
        sheetOrder: config.sheets.map((sheet, index) => sheet?.data?.id || `sheet-${index + 1}`),
        sheets: config.sheets.reduce((carry, sheet, index) => {
            const sheetId = sheet?.data?.id || `sheet-${index + 1}`;
            carry[sheetId] = merge(
                {
                    id: sheetId,
                    name: sheet.name || `Sheet${index + 1}`,
                    cellData: {},
                },
                sheet.data || {}
            );

            return carry;
        }, {}),
    };

    univer.createUnit('UNIVER_SHEET', workbookData);

    const wemFormulaPlugin = new WemFormulaPlugin({ dataApiBase: config.dataApiBase });
    wemFormulaPlugin.register(univer);

    const saveStatus = document.getElementById('save-status');
    let saveTimer = null;

    const setStatus = (text) => {
        if (saveStatus) {
            saveStatus.textContent = text;
        }
    };

    const saveWorkbook = async () => {
        setStatus('Enregistrement…');

        const workbook = univer.getActiveWorkbook();
        const snapshot = workbook ? workbook.save() : null;

        const payloadSheets = (config.sheets || []).map((sheet, index) => ({
            id: sheet.id,
            name: sheet.name || `Sheet${index + 1}`,
            data: snapshot,
        }));

        await fetch(config.saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
                Accept: 'application/json',
            },
            body: JSON.stringify({ sheets: payloadSheets }),
        });

        setStatus('Enregistré ✓');
    };

    const debounceSave = () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            saveWorkbook().catch(() => setStatus('Erreur de sauvegarde'));
        }, 2000);
    };

    const commandService = univer.__getInjector().get('commandService', null);
    if (commandService && typeof commandService.onCommandExecuted === 'function') {
        commandService.onCommandExecuted(() => debounceSave());
    }
}
