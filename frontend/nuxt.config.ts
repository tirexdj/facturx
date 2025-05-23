// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  typescript: {
    shim: false, // Recommended for Nuxt 3
    typeCheck: true // Enable type checking during development and build
  }
})
