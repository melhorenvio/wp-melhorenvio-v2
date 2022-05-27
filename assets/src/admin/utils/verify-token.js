function verifyToken() {
  return `${ajaxurl}?action=verify_token&_wpnonce=${wpApiSettings.nonce_tokens}`;
};

function getToken() {
  return `${ajaxurl}?action=get_token&_wpnonce=${wpApiSettings.nonce_tokens}`;
}

export {
  verifyToken,
  getToken
}