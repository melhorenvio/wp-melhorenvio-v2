export default function deleteSession() {
  return `${ajaxurl}?action=delete_melhor_envio_session&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_configs}`;
}