function verifyToken() {
  return `${ajaxurl}?action=verify_token&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_tokens}`;
}

function getToken() {
  return `${ajaxurl}?action=get_token&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_tokens}`;
}

function isDateTokenExpired(token) {
  // JWT Token Decode
  const base64Url = token.split(".")[1];
  const base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
  const tokenDecoded = decodeURIComponent(
    atob(base64)
      .split("")
      .map(function (c) {
        return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
      })
      .join("")
  );

  const tokenFinal = JSON.parse(tokenDecoded);
  const dateExp = new Date(parseInt(tokenFinal.exp) * 1000);
  const currentTime = new Date();

  return dateExp < currentTime
}

export {
  isDateTokenExpired,
  verifyToken,
  getToken
}