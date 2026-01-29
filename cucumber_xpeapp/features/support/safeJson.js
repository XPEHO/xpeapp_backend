// safeJson.js
// Utilitaire pour parser du JSON en toute sécurité

function safeJson(res) {
  return res.text().then(text => {
    try {
      return text ? JSON.parse(text) : {};
    } catch {
      return {};
    }
  });
}

module.exports = { safeJson };