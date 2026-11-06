FROM golang:1.27.3-alpine AS build
WORKDIR /src
COPY go.mod go.sum ./
RUN go mod download
COPY . ./
RUN CGO_ENABLED=0 go build -trimpath -ldflags='-s -w' -o /out/buras ./cmd/buras

FROM alpine:3.22
COPY --from=build /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
RUN addgroup -S buras && adduser -S -G buras buras && mkdir /data && chown buras:buras /data
COPY --from=build /out/buras /usr/local/bin/buras
USER buras
EXPOSE 8085
VOLUME /data
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s CMD wget -qO- http://127.0.0.1:8085/healthz >/dev/null || exit 1
CMD ["buras"]
