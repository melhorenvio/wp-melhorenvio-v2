export default function verifyToken() {
  return `${ajaxurl}?action=verify_token&_wpnonce=${wpApiSettings.nonce_tokens}`;
};