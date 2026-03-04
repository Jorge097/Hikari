import { useState, useEffect } from 'react';

export const useCart = () => {
    const [cart, setCart] = useState(() => {
        const savedCart = localStorage.getItem('hikari_cart');
        return savedCart ? JSON.parse(savedCart) : [];
    });

    useEffect(() => {
        localStorage.setItem('hikari_cart', JSON.stringify(cart));
        window.dispatchEvent(new Event('cart-updated'));
    }, [cart]);

    const addToCart = (product, quantity = 1) => {
        setCart(prevCart => {
            const existingItem = prevCart.find(item => item.uniqueId === product.uniqueId);
            if (existingItem) {
                return prevCart.map(item =>
                    item.uniqueId === product.uniqueId
                        ? { ...item, quantity: item.quantity + quantity }
                        : item
                );
    }
    return [...prevCart, { ...product, quantity }];
});
    };

const removeFromCart = (uniqueId) => {
    setCart(prevCart => prevCart.filter(item => item.uniqueId !== uniqueId));
};

const clearCart = () => setCart([]);

const cartTotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

return { cart, addToCart, removeFromCart, clearCart, cartTotal };
};