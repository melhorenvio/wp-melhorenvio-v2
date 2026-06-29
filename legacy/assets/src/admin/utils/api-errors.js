export function flattenApiErrors(errors) {
  if (errors == null) {
    return [];
  }
  if (typeof errors === "string") {
    return [errors];
  }
  if (!Array.isArray(errors)) {
    return [String(errors)];
  }
  const result = [];
  for (const el of errors) {
    if (Array.isArray(el)) {
      result.push(...flattenApiErrors(el));
    } else if (el != null && el !== "") {
      result.push(String(el));
    }
  }
  return result;
}

export function getErrorMessagesFromCatch(error) {
  if (error == null) {
    return ["Erro desconhecido"];
  }

  if (error.success === false && error.errors != null) {
    const flat = flattenApiErrors(error.errors);
    return flat.length ? flat : ["Não foi possível concluir a operação."];
  }

  if (error.errors != null && !error.response) {
    const flat = flattenApiErrors(error.errors);
    return flat.length ? flat : ["Não foi possível concluir a operação."];
  }

  if (error.response && error.response.data) {
    const d = error.response.data;
    if (d.errors != null) {
      const flat = flattenApiErrors(d.errors);
      if (flat.length) {
        return flat;
      }
    }
    if (d.message) {
      return [String(d.message)];
    }
  }

  if (error.message) {
    return [error.message];
  }

  return ["Erro desconhecido"];
}

export function isSuccessfulCartApiResponse(data) {
  if (!data || typeof data !== "object") {
    return false;
  }
  if (data.success === false) {
    return false;
  }
  if (data.success === true) {
    return true;
  }
  if (data.order_id || data.protocol) {
    return true;
  }
  if (data.data && (data.data.order_id || data.data.protocol)) {
    return true;
  }
  return false;
}

export function getOrderIdFromCartResponse(data) {
  if (!data || typeof data !== "object") {
    return null;
  }
  if (data.data && data.data.order_id != null) {
    return data.data.order_id;
  }
  if (data.order_id != null) {
    return data.order_id;
  }
  return null;
}

export function buildCartSuccessMessage(postId, responseData, kind = "cart") {
  if (!responseData) {
    return [
      kind === "purchase"
        ? `Pedido #${postId} concluído com sucesso.`
        : `Pedido #${postId} enviado com sucesso.`,
    ];
  }
  const protocol =
    responseData.protocol ||
    (responseData.data && responseData.data.protocol);
  const verb =
    kind === "purchase"
      ? "concluído com sucesso"
      : "enviado com sucesso";
  const line = protocol
    ? `Pedido #${postId} ${verb}. Protocolo: ${protocol}.`
    : `Pedido #${postId} ${verb}.`;
  return [line];
}
