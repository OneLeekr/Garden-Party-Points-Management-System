<template>
  <view class="p-page page-fade-in">
    <view class="header-card yy-card">
      <text class="welcome">你好，{{ userStore.userInfo?.real_name }}</text>
      <text class="role">{{ userStore.userInfo?.role_name }}</text>
    </view>

    <MenuCard icon="scan" title="扫码登记积分" desc="扫描玩家二维码发放活动积分" color="#2563eb" @click="go('/pages/admin/scan-gain')" />
    <MenuCard icon="scan" title="扫码核销积分" desc="扫描玩家二维码扣除兑换积分" color="#dc2626" @click="go('/pages/admin/scan-consume')" />
    <MenuCard icon="stats" title="项目记录" desc="查看今日发放与核销记录" color="#059669" @click="go('/pages/admin/records')" />
    <MenuCard icon="user" title="个人中心" desc="修改密码与账号信息" color="#7c3aed" @click="go('/pages/admin/profile')" />
  </view>
</template>

<script setup>
import { useUserStore } from '@/store/user.js'
import MenuCard from '@/components/MenuCard/MenuCard.vue'

const userStore = useUserStore()

function go(url) {
  uni.navigateTo({ url })
}
</script>

<style scoped>
.header-card { text-align: center; padding: 48rpx; }
.welcome { display: block; font-size: 36rpx; font-weight: 700; }
.role { display: block; font-size: 26rpx; color: var(--text-secondary); margin-top: 8rpx; }
</style>
