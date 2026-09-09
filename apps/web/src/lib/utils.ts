import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function arraysEqual(a: number[], b: number[]) {
  if (a.length !== b.length) return false;
  return a.every((val, index) => val === b[index]);
}