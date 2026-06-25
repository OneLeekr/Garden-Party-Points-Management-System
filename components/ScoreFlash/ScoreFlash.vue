<template>
  <!-- #ifdef H5 -->
  <teleport to="body">
    <view v-if="visible" class="score-flash-overlay" :class="type">
      <text class="score-flash-text">{{ sign }}{{ score }}</text>
    </view>
  </teleport>
  <!-- #endif -->
  <!-- #ifndef H5 -->
  <view v-if="visible" class="score-flash-overlay" :class="type">
    <text class="score-flash-text">{{ sign }}{{ score }}</text>
  </view>
  <!-- #endif -->
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  score: { type: Number, default: 0 },
  type: { type: String, default: 'gain' }
})

const sign = computed(() => props.type === 'gain' ? '+' : '-')
</script>

<style>
.score-flash-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  z-index: 30000;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: flashFadeOut 2.8s ease forwards;
}
.score-flash-overlay.gain { background: #059669; }
.score-flash-overlay.consume { background: #dc2626; }
.score-flash-text {
  font-size: 96px;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
  animation: scorePopIn 2.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  text-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}
@keyframes scorePopIn {
  0% { transform: scale(0.2); opacity: 0; }
  12% { transform: scale(1); opacity: 1; }
  75% { transform: scale(1); opacity: 1; }
  100% { transform: scale(1.15); opacity: 0; }
}
@keyframes flashFadeOut {
  0%, 80% { opacity: 1; }
  100% { opacity: 0; }
}
</style>
