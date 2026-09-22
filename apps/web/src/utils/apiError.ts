import axios from "axios";

interface BackendErrorPayload {
  message?: string;
  error?: string;
}

export function getApiErrorMessage(
  error: unknown,
  fallback = "Ocorreu um erro inesperado."
): string {
  if (axios.isAxiosError<BackendErrorPayload>(error)) {
    return error.response?.data?.message
      ?? error.response?.data?.error
      ?? fallback;
  }

  if (error instanceof Error && error.message) {
    return error.message;
  }

  return fallback;
}
