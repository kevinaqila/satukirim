<script setup lang="ts" generic="TData, TValue">
import type { ColumnDef, SortingState } from "@tanstack/vue-table"
import {
  FlexRender,
  getCoreRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from "@tanstack/vue-table"
import { ref } from "vue"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"

const props = withDefaults(
  defineProps<{
    columns: ColumnDef<TData, TValue>[]
    data: TData[]
    emptyText?: string
    showPagination?: boolean
    pageSize?: number
  }>(),
  {
    emptyText: "Tidak ada data.",
    showPagination: false,
    pageSize: 10,
  },
)

const sorting = ref<SortingState>([])

const table = useVueTable({
  get data() {
    return props.data
  },
  get columns() {
    return props.columns
  },
  getCoreRowModel: getCoreRowModel(),
  getPaginationRowModel: props.showPagination ? getPaginationRowModel() : undefined,
  getSortedRowModel: getSortedRowModel(),
  onSortingChange: (updaterOrValue) => {
    sorting.value =
      typeof updaterOrValue === "function"
        ? updaterOrValue(sorting.value)
        : updaterOrValue
  },
  state: {
    get sorting() {
      return sorting.value
    },
  },
  initialState: {
    pagination: {
      pageSize: props.pageSize,
    },
  },
})
</script>

<template>
  <div class="rounded-none border border-stone-200 bg-white overflow-hidden">
    <Table>
      <TableHeader>
        <TableRow
          v-for="headerGroup in table.getHeaderGroups()"
          :key="headerGroup.id"
 class="border-b border-stone-200"
        >
          <TableHead
            v-for="header in headerGroup.headers"
            :key="header.id"
            :class="header.column.columnDef.meta?.headerClass"
          >
            <FlexRender
              v-if="!header.isPlaceholder"
              :render="header.column.columnDef.header"
              :props="header.getContext()"
            />
          </TableHead>
        </TableRow>
      </TableHeader>
 <TableBody class="divide-y divide-stone-100">
        <template v-if="table.getRowModel().rows?.length">
          <TableRow
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            :data-state="row.getIsSelected() ? 'selected' : undefined"
 class="group hover:bg-stone-50 transition-colors"
          >
            <TableCell
              v-for="cell in row.getVisibleCells()"
              :key="cell.id"
              :class="cell.column.columnDef.meta?.cellClass"
            >
              <FlexRender
                :render="cell.column.columnDef.cell"
                :props="cell.getContext()"
              />
            </TableCell>
          </TableRow>
        </template>
        <template v-else>
          <TableRow>
            <TableCell
              :colspan="columns.length"
 class="h-24 text-center text-sm text-stone-500"
            >
              <slot name="empty">
                {{ emptyText }}
              </slot>
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>

    <div
      v-if="showPagination && table.getPageCount() > 1"
 class="flex items-center justify-between px-4 py-3 border-t border-stone-200 text-xs text-stone-600"
    >
      <span>
        Halaman {{ table.getState().pagination.pageIndex + 1 }} dari {{ table.getPageCount() }}
      </span>
      <div class="flex items-center gap-1.5">
        <button
          type="button"
          :disabled="!table.getCanPreviousPage()"
          @click="table.previousPage()"
 class="px-2.5 py-1 rounded border border-stone-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-stone-50 transition-colors"
        >
          Sebelumnya
        </button>
        <button
          type="button"
          :disabled="!table.getCanNextPage()"
          @click="table.nextPage()"
 class="px-2.5 py-1 rounded border border-stone-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-stone-50 transition-colors"
        >
          Berikutnya
        </button>
      </div>
    </div>
  </div>
</template>
