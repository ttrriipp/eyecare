(function () {
  var KEY = "eyecare_proto_cart";

  function getCart() {
    try {
      return JSON.parse(localStorage.getItem(KEY) || "[]");
    } catch (e) {
      return [];
    }
  }

  function setCart(items) {
    localStorage.setItem(KEY, JSON.stringify(items));
  }

  window.EyeCareCart = {
    get: getCart,
    clear: function () {
      setCart([]);
    },
    add: function (variant) {
      var items = getCart();
      var found = items.find(function (i) {
        return i.product_variant_id === variant.product_variant_id;
      });
      if (found) {
        found.quantity += variant.quantity || 1;
      } else {
        items.push({
          product_variant_id: variant.product_variant_id,
          name: variant.name,
          unit_price: variant.unit_price,
          quantity: variant.quantity || 1,
        });
      }
      setCart(items);
      return items;
    },
    remove: function (variantId) {
      var items = getCart().filter(function (i) {
        return i.product_variant_id !== variantId;
      });
      setCart(items);
      return items;
    },
    total: function () {
      return getCart().reduce(function (sum, i) {
        return sum + Number(i.unit_price) * i.quantity;
      }, 0);
    },
    renderBadge: function (el) {
      if (!el) return;
      var n = getCart().reduce(function (s, i) {
        return s + i.quantity;
      }, 0);
      el.textContent = n ? "(" + n + ")" : "";
    },
  };
})();
