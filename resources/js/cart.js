const MAX_PURCHASABLE_QUANTITY = 100;

const normalizeQuantity = (quantity) => {
    const parsedQuantity =
        typeof quantity === "number"
            ? quantity
            : typeof quantity === "string" && quantity.trim() !== ""
              ? Number(quantity)
              : Number.NaN;

    return Number.isFinite(parsedQuantity)
        ? Math.min(
              MAX_PURCHASABLE_QUANTITY,
              Math.max(1, Math.trunc(parsedQuantity)),
          )
        : 1;
};

document.addEventListener("alpine:init", () => {
    // Cart store with localStorage persistence
    const savedCart = localStorage.getItem("cart");
    let initialItems = [];

    if (savedCart) {
        try {
            const parsedCart = JSON.parse(savedCart);
            initialItems = Array.isArray(parsedCart)
                ? parsedCart.map((item) => ({
                      ...item,
                      qty: normalizeQuantity(item.qty),
                  }))
                : [];
        } catch {
            initialItems = [];
        }
    }

    // Cart store definition
    Alpine.store("cart", {
        items: initialItems,
        save() {
            localStorage.setItem("cart", JSON.stringify(this.items));
        },
        get count() {
            return this.items.reduce((total, item) => total + item.qty, 0);
        },
        get total() {
            return this.items.reduce(
                (total, item) => total + item.price * item.qty,
                0,
            );
        },
        get groupedByShop() {
            const groups = {};
            this.items.forEach((item) => {
                const shopId = item.shop_id ?? "unknown";
                if (!groups[shopId]) {
                    groups[shopId] = {
                        shop_id: shopId,
                        shop_name: item.shop_name ?? "Toko",
                        items: [],
                    };
                }
                groups[shopId].items.push(item);
            });
            return Object.values(groups);
        },
        add(product) {
            const productId = Number(product.id);
            const existing = this.items.find((item) => Number(item.id) === productId);

            if (existing) {
                existing.qty = normalizeQuantity(
                    normalizeQuantity(existing.qty) + normalizeQuantity(product.qty),
                );
            } else {
                this.items.push({ ...product, id: productId, qty: normalizeQuantity(product.qty) });
            }

            this.save();
        },
        upsert(product) {
            const productId = Number(product.id);

            if (!Number.isSafeInteger(productId) || productId < 1) return;

            const productIndex = this.items.findIndex((item) => Number(item.id) === productId);
            const cartItem = {
                ...(productIndex >= 0 ? this.items[productIndex] : {}),
                ...product,
                id: productId,
                qty: normalizeQuantity(product.qty),
            };

            if (productIndex >= 0) {
                this.items.splice(productIndex, 1, cartItem);
            } else {
                this.items.push(cartItem);
            }

            this.save();
        },
        remove(id) {
            const productId = Number(id);
            this.items = this.items.filter((item) => Number(item.id) !== productId);
            this.save();
        },
        removeMany(ids) {
            const productIds = new Set(
                (Array.isArray(ids) ? ids : [])
                    .map(Number)
                    .filter((id) => Number.isSafeInteger(id) && id > 0),
            );

            this.items = this.items.filter((item) => !productIds.has(Number(item.id)));
            this.save();
        },
        updateQty(id, qty) {
            const productId = Number(id);
            const item = this.items.find((cartItem) => Number(cartItem.id) === productId);

            if (item) {
                item.qty = normalizeQuantity(qty);
                this.save();
            }
        },
        clear() {
            this.items = [];
            this.save();
        },
    });
});
