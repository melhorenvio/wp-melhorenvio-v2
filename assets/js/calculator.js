function mascara(t, mask) {
    let postal_code = t.value.substr(t.value.length - 1);
    if (!isNaN(postal_code)) {
        let i = t.value.length;
        let saida = mask.substring(1, 0);
        let texto = mask.substring(i);
        if (texto.substring(0, 1) != saida) {
            t.value += texto.substring(0, 1);
        }
    } else {
        t.value = t.value.slice(0, -1);
    }
}
