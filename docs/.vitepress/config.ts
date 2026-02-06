import { defineConfig } from 'vitepress'
import { tabsMarkdownPlugin } from 'vitepress-plugin-tabs'

export default defineConfig({
  title: 'DocTest',
  description: 'Test your PHP documentation examples automatically',
  head: [
    ['link', { rel: 'icon', href: '/doctest-logo.svg' }],
    ['link', { rel: 'preconnect', href: 'https://fonts.googleapis.com' }],
    ['link', { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' }],
    [
      'link',
      {
        href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap',
        rel: 'stylesheet',
      },
    ],
  ],
  markdown: {
    config(md) {
      md.use(tabsMarkdownPlugin)
    },
  },
  themeConfig: {
    logo: '/doctest-logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/getting-started' },
      { text: 'Assertions', link: '/assertions/' },
      { text: 'CLI', link: '/cli/' },
      { text: 'Configuration', link: '/configuration/' },
      { text: 'Integrations', link: '/integrations/' },
    ],
    sidebar: [
      {
        text: 'Guide',
        items: [
          { text: 'Getting Started', link: '/guide/getting-started' },
          { text: 'Installation', link: '/guide/installation' },
          { text: 'How It Works', link: '/guide/how-it-works' },
          { text: 'Writing Testable Docs', link: '/guide/writing-testable-docs' },
        ],
      },
      {
        text: 'Assertions',
        items: [
          { text: 'Overview', link: '/assertions/' },
          { text: 'Output', link: '/assertions/output' },
          { text: 'OutputContains', link: '/assertions/output-contains' },
          { text: 'OutputMatches', link: '/assertions/output-matches' },
          { text: 'OutputJson', link: '/assertions/output-json' },
          { text: 'Expect', link: '/assertions/expect' },
          { text: 'Result Comment (=>)', link: '/assertions/result-comment' },
          { text: 'HTML Comment', link: '/assertions/html-comment' },
          { text: 'Wildcards', link: '/wildcards/' },
        ],
      },
      {
        text: 'Attributes',
        items: [
          { text: 'Overview', link: '/attributes/' },
          { text: 'ignore', link: '/attributes/ignore' },
          { text: 'no_run', link: '/attributes/no-run' },
          { text: 'throws', link: '/attributes/throws' },
          { text: 'parse_error', link: '/attributes/parse-error' },
          { text: 'group', link: '/attributes/group' },
          { text: 'setup / teardown', link: '/attributes/setup-teardown' },
        ],
      },
      {
        text: 'CLI',
        items: [
          { text: 'Usage', link: '/cli/' },
          { text: 'Options Reference', link: '/cli/options' },
          { text: 'Exit Codes', link: '/cli/exit-codes' },
        ],
      },
      {
        text: 'Configuration',
        items: [
          { text: 'Options', link: '/configuration/' },
          { text: 'Reporters', link: '/reporters/' },
          { text: 'Console Reporter', link: '/reporters/console' },
          { text: 'JUnit XML Reporter', link: '/reporters/junit' },
          { text: 'JSON Reporter', link: '/reporters/json' },
          { text: 'Framework Bootstrap', link: '/framework-bootstrap/' },
        ],
      },
      {
        text: 'Integrations',
        items: [
          { text: 'CI/CD', link: '/ci-cd/' },
          { text: 'AI Agents', link: '/integrations/' },
          { text: 'Shiki / VitePress', link: '/shiki/' },
        ],
      },
      {
        text: 'Advanced',
        items: [
          { text: 'Execution Model', link: '/advanced/execution-model' },
          { text: 'Output Comparison', link: '/advanced/output-comparison' },
          { text: 'Security', link: '/advanced/security' },
        ],
      },
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/testflowlabs/doctest' },
    ],
    search: {
      provider: 'local',
    },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright TestFlowLabs',
    },
  },
})
