import DefaultTheme from 'vitepress/theme'
import { enhanceAppWithTabs } from 'vitepress-plugin-tabs/client'
import { setupHiddenLinesToggle } from 'shiki-hide-lines'
import type { Theme } from 'vitepress'
import 'shiki-hide-lines/style.css'
import './style.css'

export default {
  extends: DefaultTheme,
  enhanceApp({ app }) {
    enhanceAppWithTabs(app)
    if (typeof window !== 'undefined') {
      setupHiddenLinesToggle()
    }
  },
} satisfies Theme
