<script setup lang="ts">
// Import all icon components
import SvgAirplane from '@/Icons/SvgAirplane.vue';
import SvgArrowPath from '@/Icons/SvgArrowPath.vue';
import SvgArrowPathSpin from '@/Icons/SvgArrowPathSpin.vue';
import SvgArrowRight from '@/Icons/SvgArrowRight.vue';
import SvgArrowSignOut from '@/Icons/SvgArrowSignOut.vue';
import SvgBadgeCheck from '@/Icons/SvgBadgeCheck.vue';
import SvgBars3 from '@/Icons/SvgBars3.vue';
import SvgBeaker from '@/Icons/SvgBeaker.vue';
import SvgBookOpen from '@/Icons/SvgBookOpen.vue';
import SvgBuildingOffice2 from '@/Icons/SvgBuildingOffice2.vue';
import SvgBuildingStorefront from '@/Icons/SvgBuildingStorefront.vue';
import SvgCalendar from '@/Icons/SvgCalendar.vue';
import SvgCalendarDates from '@/Icons/SvgCalendarDates.vue';
import SvgCalendarDays from '@/Icons/SvgCalendarDays.vue';
import SvgCaretLeft from '@/Icons/SvgCaretLeft.vue';
import SvgCaretRight from '@/Icons/SvgCaretRight.vue';
import SvgChartBar from '@/Icons/SvgChartBar.vue';
import SvgChartBar3 from '@/Icons/SvgChartBar3.vue';
import SvgChartLine from '@/Icons/SvgChartLine.vue';
import SvgChat from '@/Icons/SvgChat.vue';
import SvgChatBubbleLeftRight from '@/Icons/SvgChatBubbleLeftRight.vue';
import SvgChatBubbleOval from '@/Icons/SvgChatBubbleOval.vue';
import SvgChatDots from '@/Icons/SvgChatDots.vue';
import SvgCheck from '@/Icons/SvgCheck.vue';
import SvgCheckCircle from '@/Icons/SvgCheckCircle.vue';
import SvgChevronDown from '@/Icons/SvgChevronDown.vue';
import SvgChevronLeft from '@/Icons/SvgChevronLeft.vue';
import SvgChevronRight from '@/Icons/SvgChevronRight.vue';
import SvgChevronUp from '@/Icons/SvgChevronUp.vue';
import SvgChevronUpDown from '@/Icons/SvgChevronUpDown.vue';
import SvgClipboard from '@/Icons/SvgClipboard.vue';
import SvgClock from '@/Icons/SvgClock.vue';
import SvgCloudArrowUp from '@/Icons/SvgCloudArrowUp.vue';
import SvgCog from '@/Icons/SvgCog.vue';
import SvgComputerDesktop from '@/Icons/SvgComputerDesktop.vue';
import SvgCube from '@/Icons/SvgCube.vue';
import SvgCurrencyDollar from '@/Icons/SvgCurrencyDollar.vue';
import SvgDashboard from '@/Icons/SvgDashboard.vue';
import SvgDevicePhoneMobile from '@/Icons/SvgDevicePhoneMobile.vue';
import SvgDeviceTablet from '@/Icons/SvgDeviceTablet.vue';
import SvgDocument from '@/Icons/SvgDocument.vue';
import SvgDollar3 from '@/Icons/SvgDollar3.vue';
import SvgDot from '@/Icons/SvgDot.vue';
import SvgDownload from '@/Icons/SvgDownload.vue';
import SvgDragHandle from '@/Icons/SvgDragHandle.vue';
import SvgEnvelope from '@/Icons/SvgEnvelope.vue';
import SvgEnvelope2 from '@/Icons/SvgEnvelope2.vue';
import SvgExclamationTriangle from '@/Icons/SvgExclamationTriangle.vue';
import SvgFacebook from '@/Icons/SvgFacebook.vue';
import SvgFlag from '@/Icons/SvgFlag.vue';
import SvgFolders from '@/Icons/SvgFolders.vue';
import SvgGift from '@/Icons/SvgGift.vue';
import SvgGoogle from '@/Icons/SvgGoogle.vue';
import SvgGripVertical from '@/Icons/SvgGripVertical.vue';
import SvgHome from '@/Icons/SvgHome.vue';
import SvgHomeModern from '@/Icons/SvgHomeModern.vue';
import SvgInformationCircle from '@/Icons/SvgInformationCircle.vue';
import SvgInstagram from '@/Icons/SvgInstagram.vue';
import SvgLinkedin from '@/Icons/SvgLinkedin.vue';
import SvgListBullet from '@/Icons/SvgListBullet.vue';
import SvgLocationDot from '@/Icons/SvgLocationDot.vue';
import SvgLockClosed from '@/Icons/SvgLockClosed.vue';
import SvgMagnifyingGlass from '@/Icons/SvgMagnifyingGlass.vue';
import SvgMap from '@/Icons/SvgMap.vue';
import SvgMastodon from '@/Icons/SvgMastodon.vue';
import SvgMegaphone from '@/Icons/SvgMegaphone.vue';
import SvgMinusCircle from '@/Icons/SvgMinusCircle.vue';
import SvgMoon from '@/Icons/SvgMoon.vue';
import SvgPaperAirplane from '@/Icons/SvgPaperAirplane.vue';
import SvgPaperclip from '@/Icons/SvgPaperclip.vue';
import SvgPause from '@/Icons/SvgPause.vue';
import SvgPencil from '@/Icons/SvgPencil.vue';
import SvgPhoto from '@/Icons/SvgPhoto.vue';
import SvgPlay from '@/Icons/SvgPlay.vue';
import SvgPlayCircle from '@/Icons/SvgPlayCircle.vue';
import SvgPlus from '@/Icons/SvgPlus.vue';
import SvgPlusCircle from '@/Icons/SvgPlusCircle.vue';
import SvgRefresh from '@/Icons/SvgRefresh.vue';
import SvgRoute from '@/Icons/SvgRoute.vue';
import SvgShieldCheck from '@/Icons/SvgShieldCheck.vue';
import SvgSparkles from '@/Icons/SvgSparkles.vue';
import SvgStar from '@/Icons/SvgStar.vue';
import SvgSun from '@/Icons/SvgSun.vue';
import SvgTableCells from '@/Icons/SvgTableCells.vue';
import SvgTableLayout from '@/Icons/SvgTableLayout.vue';
import SvgThumbsDown from '@/Icons/SvgThumbsDown.vue';
import SvgThumbsUp from '@/Icons/SvgThumbsUp.vue';
import SvgTicket from '@/Icons/SvgTicket.vue';
import SvgTrash from '@/Icons/SvgTrash.vue';
import SvgTrendingUp from '@/Icons/SvgTrendingUp.vue';
import SvgTrophy from '@/Icons/SvgTrophy.vue';
import SvgTwitter from '@/Icons/SvgTwitter.vue';
import SvgUser from '@/Icons/SvgUser.vue';
import SvgUserGroup from '@/Icons/SvgUserGroup.vue';
import SvgUsers from '@/Icons/SvgUsers.vue';
import SvgWrench from '@/Icons/SvgWrench.vue';
import SvgWrenchScrewdriver from '@/Icons/SvgWrenchScrewdriver.vue';
import SvgXCircle from '@/Icons/SvgXCircle.vue';
import SvgXMark from '@/Icons/SvgXMark.vue';
import SvgYouTube from '@/Icons/SvgYouTube.vue';
import { computed } from 'vue';

interface Props {
    name: string;
}

const props = defineProps<Props>();

// Map icon names to components
const iconMap: Record<string, any> = {
    airplane: SvgAirplane,
    'arrow-path': SvgArrowPath,
    'arrow-path-spin': SvgArrowPathSpin,
    'arrow-right': SvgArrowRight,
    'arrow-sign-out': SvgArrowSignOut,
    'badge-check': SvgBadgeCheck,
    'bars-3': SvgBars3,
    beaker: SvgBeaker,
    'building-office-2': SvgBuildingOffice2,
    'building-storefront': SvgBuildingStorefront,
    calendar: SvgCalendar,
    'calendar-dates': SvgCalendarDates,
    'calendar-days': SvgCalendarDays,
    'caret-left': SvgCaretLeft,
    'caret-right': SvgCaretRight,
    'chart-bar': SvgChartBar,
    'chart-bar-3': SvgChartBar3,
    'chart-line': SvgChartLine,
    chat: SvgChat,
    'chat-bubble-left-right': SvgChatBubbleLeftRight,
    'chat-bubble-oval': SvgChatBubbleOval,
    'chat-dots': SvgChatDots,
    check: SvgCheck,
    'check-circle': SvgCheckCircle,
    'chevron-down': SvgChevronDown,
    'chevron-left': SvgChevronLeft,
    'chevron-right': SvgChevronRight,
    'chevron-up': SvgChevronUp,
    'chevron-up-down': SvgChevronUpDown,
    clipboard: SvgClipboard,
    clock: SvgClock,
    'cloud-arrow-up': SvgCloudArrowUp,
    cog: SvgCog,
    'cog-6-tooth': SvgCog, // alias
    cube: SvgCube,
    'computer-desktop': SvgComputerDesktop,
    'currency-dollar': SvgCurrencyDollar,
    dashboard: SvgDashboard,
    document: SvgDocument,
    download: SvgDownload,
    'device-phone-mobile': SvgDevicePhoneMobile,
    'dollar-3': SvgDollar3,
    dot: SvgDot,
    'drag-handle': SvgDragHandle,
    envelope: SvgEnvelope,
    'envelope-2': SvgEnvelope2,
    'exclamation-triangle': SvgExclamationTriangle,
    facebook: SvgFacebook,
    flag: SvgFlag,
    folders: SvgFolders,
    gift: SvgGift,
    google: SvgGoogle,
    'grip-vertical': SvgGripVertical,
    home: SvgHome,
    'home-modern': SvgHomeModern,
    instagram: SvgInstagram,
    'information-circle': SvgInformationCircle,
    linkedin: SvgLinkedin,
    'list-bullet': SvgListBullet,
    'magnifying-glass': SvgMagnifyingGlass,
    map: SvgMap,
    mastodon: SvgMastodon,
    megaphone: SvgMegaphone,
    'minus-circle': SvgMinusCircle,
    moon: SvgMoon,
    'paper-airplane': SvgPaperAirplane,
    paperclip: SvgPaperclip,
    pause: SvgPause,
    pencil: SvgPencil,
    play: SvgPlay,
    'play-circle': SvgPlayCircle,
    plus: SvgPlus,
    'plus-circle': SvgPlusCircle,
    route: SvgRoute,
    settings: SvgCog, // alias
    search: SvgMagnifyingGlass, // alias
    spinner: SvgArrowPathSpin, // alias
    'building-office': SvgBuildingStorefront, // alias
    'shield-check': SvgShieldCheck,
    'x-circle': SvgXCircle,
    'device-tablet': SvgDeviceTablet,
    'lock-closed': SvgLockClosed,
    photo: SvgPhoto,
    'book-open': SvgBookOpen,
    'location-dot': SvgLocationDot,
    refresh: SvgRefresh,
    sparkles: SvgSparkles,
    star: SvgStar,
    sun: SvgSun,
    'table-cells': SvgTableCells,
    'table-layout': SvgTableLayout,
    'thumbs-down': SvgThumbsDown,
    'thumbs-up': SvgThumbsUp,
    ticket: SvgTicket,
    trash: SvgTrash,
    'trending-up': SvgTrendingUp,
    trophy: SvgTrophy,
    twitter: SvgTwitter,
    user: SvgUser,
    'user-group': SvgUserGroup,
    users: SvgUsers,
    wrench: SvgWrench,
    'wrench-screwdriver': SvgWrenchScrewdriver,
    'x-mark': SvgXMark,
    youtube: SvgYouTube,
};

const iconComponent = computed(() => {
    return iconMap[props.name] || null;
});
</script>

<template>
    <!-- Forward all attributes (class, style, size, etc.) to the SVG root -->
    <component :is="iconComponent" v-if="iconComponent" v-bind="$attrs" />
    <div v-else class="size-5 rounded bg-gray-200" />
</template>
