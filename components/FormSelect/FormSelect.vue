<template>
  <view class="form-select" :class="{ open }">
    <view class="select-trigger yy-input" @click.stop="toggle">
      <text class="select-text" :class="{ placeholder: !displayText }">{{ displayText || placeholder }}</text>
      <SvgIcon name="chevronRight" :size="16" color="#94a3b8" class="select-arrow" />
    </view>
    <view v-if="open" class="select-dropdown">
      <view
        v-for="(item, index) in options"
        :key="getKey(item, index)"
        class="select-option"
        :class="{ active: index === modelValue }"
        @click.stop="select(index)"
      >
        {{ item[labelKey] }}
      </view>
      <view v-if="!options.length" class="select-empty">暂无选项</view>
    </view>
  </view>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'

const props = defineProps({
  modelValue: { type: Number, default: -1 },
  options: { type: Array, default: () => [] },
  labelKey: { type: String, default: 'label' },
  valueKey: { type: String, default: 'id' },
  placeholder: { type: String, default: '请选择' }
})

const emit = defineEmits(['update:modelValue', 'change'])

const open = ref(false)

const displayText = computed(() => {
  if (props.modelValue < 0 || !props.options[props.modelValue]) return ''
  return props.options[props.modelValue][props.labelKey]
})

function getKey(item, index) {
  return item[props.valueKey] ?? index
}

function toggle() {
  open.value = !open.value
}

function select(index) {
  open.value = false
  emit('update:modelValue', index)
  emit('change', { index, item: props.options[index] })
}

watch(() => props.modelValue, () => {
  open.value = false
})
</script>

<style scoped>
.form-select { position: relative; width: 100%; }
.select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  box-sizing: border-box;
  height: 88rpx;
  line-height: normal;
}
.select-text { flex: 1; font-size: 28rpx; color: var(--text-primary); }
.select-text.placeholder { color: var(--text-muted); }
.select-arrow { transform: rotate(90deg); flex-shrink: 0; }
.form-select.open .select-arrow { transform: rotate(-90deg); }
.select-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 8rpx);
  z-index: 100;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 16rpx;
  box-shadow: var(--shadow-lg);
  max-height: 360rpx;
  overflow-y: auto;
}
.select-option {
  padding: 24rpx 28rpx;
  font-size: 28rpx;
  color: var(--text-primary);
  border-bottom: 1px solid var(--border);
}
.select-option:last-child { border-bottom: none; }
.select-option:active { background: var(--primary-soft); }
.select-option.active { color: var(--primary); font-weight: 600; background: var(--primary-soft); }
.select-empty { padding: 32rpx; text-align: center; color: var(--text-muted); font-size: 26rpx; }
</style>
