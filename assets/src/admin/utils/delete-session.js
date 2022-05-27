export default function deleteSession() {
  return `${ajaxurl}?action=delete_melhor_envio_session&_wpnonce=${wpApiSettings.nonce_configs}`;
}