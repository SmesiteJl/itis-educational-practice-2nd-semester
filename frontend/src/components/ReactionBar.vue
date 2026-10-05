<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import type { Reaction } from '@/types'

const REACTIONS = ['👍', '👎', '❤️', '😂', '😮', '😢', '🔥', '🎉']

const props = defineProps<{
  reactions: Reaction[]
  myNickname: string | null
}>()

const emit = defineEmits<{
  react: [emoji: string]
}>()

const showPicker = ref(false)
const pickerButton = ref<HTMLButtonElement | null>(null)
const picker = ref<HTMLElement | null>(null)
const pickerStyle = ref({ top: '0px', left: '0px' })

async function togglePicker() {
  showPicker.value = !showPicker.value
  if (!showPicker.value) return

  await nextTick()
  if (!pickerButton.value || !picker.value) return

  const button = pickerButton.value.getBoundingClientRect()
  const message = pickerButton.value.closest('.message')?.getBoundingClientRect() ?? button
  const messages = pickerButton.value.closest('.messages')?.getBoundingClientRect()
  const menu = picker.value.getBoundingClientRect()
  const top = message.top - menu.height - 8
  const leftEdge = (messages?.left ?? 0) + 8
  const rightEdge = (messages?.right ?? window.innerWidth) - menu.width - 8
  pickerStyle.value = {
    top: `${top >= 8 ? top : message.bottom + 8}px`,
    left: `${Math.max(leftEdge, Math.min(button.left, rightEdge))}px`,
  }
}

function closeOnOutsideClick(event: PointerEvent) {
  if (event.target instanceof Node && !picker.value?.contains(event.target) && !pickerButton.value?.contains(event.target)) {
    showPicker.value = false
  }
}

function closePicker() {
  showPicker.value = false
}

function closeOnEscape(event: KeyboardEvent) {
  if (event.key === 'Escape') closePicker()
}

onMounted(() => {
  document.addEventListener('pointerdown', closeOnOutsideClick)
  document.addEventListener('scroll', closePicker, true)
  window.addEventListener('resize', closePicker)
  document.addEventListener('keydown', closeOnEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', closeOnOutsideClick)
  document.removeEventListener('scroll', closePicker, true)
  window.removeEventListener('resize', closePicker)
  document.removeEventListener('keydown', closeOnEscape)
})

function react(emoji: string) {
  emit('react', emoji)
  showPicker.value = false
}

function iReacted(users: string[]) {
  return props.myNickname !== null && users.includes(props.myNickname)
}
</script>

<template>
  <div class="reactions">
    <button
      v-for="reaction in reactions"
      :key="reaction.emoji"
      class="reaction"
      :class="{ active: iReacted(reaction.users) }"
      :title="reaction.users.join(', ')"
      @click="react(reaction.emoji)"
    >
      {{ reaction.emoji }} {{ reaction.users.length }}
    </button>

    <div>
      <button ref="pickerButton" class="add-reaction" title="Поставить реакцию" type="button" :aria-expanded="showPicker" @click="togglePicker">
        +
      </button>
    </div>
    <Teleport to="body">
      <div v-if="showPicker" ref="picker" class="reaction-picker" :style="pickerStyle">
        <button v-for="emoji in REACTIONS" :key="emoji" @click="react(emoji)">
          {{ emoji }}
        </button>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.reactions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
}

.reaction {
  border: 1px solid #ced4da;
  background: #f8f9fa;
  border-radius: 12px;
  padding: 0 8px;
  font-size: 13px;
}

.reaction.active {
  border-color: #0d6efd;
  background: #e7f1ff;
}

.add-reaction {
  border: 1px solid transparent;
  background: none;
  color: #6c757d;
  border-radius: 12px;
  padding: 0 7px;
  font-size: 13px;
}

.add-reaction:hover {
  border-color: #ced4da;
}

.reaction-picker {
  position: fixed;
  z-index: 1000;
  display: flex;
  background: #fff;
  border: 1px solid #ced4da;
  border-radius: 6px;
  padding: 3px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.reaction-picker button {
  border: none;
  background: none;
  font-size: 18px;
  padding: 2px 4px;
  border-radius: 4px;
}

.reaction-picker button:hover {
  background: #e9ecef;
}
</style>
