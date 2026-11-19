// Textes du tableau de bord (resources/lang/*/dashboard_ui.php), remplis par HomeDashboard
// avant le rendu de la grille et des widgets ; chaque module garde son repli français.
export const dashboardTexts = {};

export function setDashboardTexts(texts) {
    Object.keys(dashboardTexts).forEach((key) => { delete dashboardTexts[key]; });
    Object.assign(dashboardTexts, texts ?? {});
}
