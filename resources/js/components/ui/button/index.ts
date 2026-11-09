import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
"inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-semibold transition-all duration-150 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] active:scale-[0.99] aria-invalid:ring-destructive/20 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-[#853953] text-white hover:bg-[#612D53] shadow-xs",
        destructive:
"bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/20",
        outline:
          "border border-stone-200 bg-background shadow-xs hover:bg-stone-50 hover:text-stone-900",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost:
"hover:bg-accent hover:text-accent-foreground",
        link: "text-primary underline-offset-4 hover:underline",
      },
      size: {
        "default": "h-10.5 px-4 py-2 has-[>svg]:px-3",
        "sm": "h-8.5 rounded-lg gap-1.5 px-3 has-[>svg]:px-2 text-xs",
        "lg": "h-11.5 rounded-xl px-6 has-[>svg]:px-4 text-base",
        "icon": "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
