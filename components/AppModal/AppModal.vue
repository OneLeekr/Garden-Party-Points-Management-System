<template>
  <!-- #ifdef H5 -->
  <teleport to="body">
    <view v-if="visible" class="app-modal-mask" @click.self="onMaskClick" @touchmove.stop.prevent>
      <view
        class="app-modal-panel"
        :class="size"
        @click.stop
        @tap.stop
        @mousedown.stop
        @touchstart.stop
      >
        <slot></slot>
      </view>
    </view>
  </teleport>
  <!-- #endif -->
  <!-- #ifndef H5 -->
  <view v-if="visible" class="app-modal-mask" @click.self="onMaskClick" @touchmove.stop.prevent>
    <view
      class="app-modal-panel"
      :class="size"
      @click.stop
      @tap.stop
    >
      <slot></slot>
    </view>
  </view>
  <!-- #endif -->
</template>

<script setup>
import { watch, onUnmounted } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  size: { type: String, default: 'md' },
  closeOnMask: { type: Boolean, default: true }
})

const emit = defineEmits(['update:visible', 'close'])

function onMaskClick() {
  if (!props.closeOnMask) return
  emit('update:visible', false)
  emit('close')
}

watch(() => props.visible, (v) => {
  // #ifdef H5
  if (typeof document !== 'undefined') {
    document.body.style.overflow = v ? 'hidden' : ''
  }
  // #endif
})

onUnmounted(() => {
  // #ifdef H5
  if (typeof document !== 'undefined') {
    document.body.style.overflow = ''
  }
  // #endif
})
</script>

<style>
.app-modal-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  min-height: 100%;
  background: rgba(15, 23, 42, 0.52);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 32rpx;
  box-sizing: border-box;
  animation: appModalFadeIn 0.22s ease;
}

.app-modal-panel {
  width: 100%;
  max-width: 640px;
  background: var(--bg-card, #fff);
  border-radius: 28rpx;
  padding: 40rpx;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.2);
  animation: appModalSlideUp 0.28s cubic-bezier(0.4, 0, 0.2, 1);
  -webkit-overflow-scrolling: touch;
}

.app-modal-panel.lg { max-width: 720px; }

@keyframes appModalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes appModalSlideUp {
  from { opacity: 0; transform: translateY(24rpx) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
