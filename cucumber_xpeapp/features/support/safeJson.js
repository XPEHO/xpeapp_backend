// safeJson.js
// Utilitaire pour parser du JSON en toute sécurité

export function safeJson(res) {
  return res.text().then(text => {
    try {
      return text ? JSON.parse(text) : {};
    } catch {
      return {};
    }
  });
}