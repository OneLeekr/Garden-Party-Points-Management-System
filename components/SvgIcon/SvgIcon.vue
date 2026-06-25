<template>
  <!-- #ifdef H5 -->
  <svg
    class="svg-icon"
    :width="sizePx"
    :height="sizePx"
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <path
      :d="path"
      :stroke="color"
      :fill="filled ? color : 'none'"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
  <!-- #endif -->
  <!-- #ifndef H5 -->
  <image class="svg-icon-img" :src="iconSrc" :style="{ width: sizePx + 'px', height: sizePx + 'px' }" mode="aspectFit" />
  <!-- #endif -->
</template>

<script setup>
import { computed } from 'vue'
import { icons } from './icons.js'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 24 },
  color: { type: String, default: 'currentColor' },
  filled: { type: Boolean, default: false }
})

const sizePx = computed(() => Number(props.size))
const path = computed(() => icons[props.name] || icons.home)
const iconSrc = computed(() => {
  const c = props.color.replace('#', '%23')
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="${path.value}" stroke="${props.color}" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`
  return `data:image/svg+xml,${encodeURIComponent(svg)}`
})
</script>

<style scoped>
.svg-icon { display: block; flex-shrink: 0; }
.svg-icon-img { display: block; flex-shrink: 0; }
</style>
