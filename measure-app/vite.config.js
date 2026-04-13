import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd())

  return {
    // VITE_CDN_BASE 有值时，静态资源从 CDN 加载（国内加速）；否则用相对路径
    base: env.VITE_CDN_BASE || '/',

    plugins: [vue()],

    resolve: {
      alias: {
        '@': resolve(__dirname, 'src')
      }
    },

    server: {
      host: '0.0.0.0',
      port: 3000
    },

    build: {
      // 分块策略：将 vendor 单独抽出，便于 CDN 长期缓存
      rollupOptions: {
        output: {
          chunkFileNames:  'assets/js/[name]-[hash].js',
          entryFileNames:  'assets/js/[name]-[hash].js',
          assetFileNames:  'assets/[ext]/[name]-[hash].[ext]',
          manualChunks(id) {
            if (id.includes('node_modules')) {
              return 'vendor'
            }
          }
        }
      }
    }
  }
})
