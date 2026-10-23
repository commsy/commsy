{{- define "commsy.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" -}}
{{- end -}}

{{- define "commsy.fullname" -}}
{{- if .Values.fullnameOverride -}}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- $name := default .Chart.Name .Values.nameOverride -}}
{{- if contains $name .Release.Name -}}
{{- .Release.Name | trunc 63 | trimSuffix "-" -}}
{{- else -}}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" -}}
{{- end -}}
{{- end -}}
{{- end -}}

{{- define "commsy.labels" -}}
helm.sh/chart: {{ printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" }}
{{ include "commsy.selectorLabels" . }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end -}}

{{- define "commsy.selectorLabels" -}}
app.kubernetes.io/name: {{ include "commsy.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end -}}

{{- define "commsy.image" -}}
{{- if .Values.image.digest -}}
{{- printf "%s@%s" .Values.image.repository .Values.image.digest -}}
{{- else -}}
{{- printf "%s:%s" .Values.image.repository (default .Chart.AppVersion .Values.image.tag) -}}
{{- end -}}
{{- end -}}

{{- define "commsy.filesClaim" -}}
{{- default (printf "%s-files" (include "commsy.fullname" .)) .Values.persistence.files.existingClaim -}}
{{- end -}}

{{/* Environment shared by web and workers. */}}
{{- define "commsy.env" -}}
- name: APP_ENV
  value: prod
- name: SERVER_NAME
  value: {{ .Values.commsy.serverName | quote }}
- name: BASE_URI
  value: {{ .Values.commsy.baseUri | quote }}
{{- with .Values.commsy.emailFrom }}
- name: EMAIL_FROM
  value: {{ . | quote }}
{{- end }}
{{- with .Values.commsy.trustedProxies }}
- name: TRUSTED_PROXIES
  value: {{ . | quote }}
{{- end }}
- name: ELASTICSEARCH_URL
  value: {{ .Values.commsy.elasticsearchUrl | quote }}
- name: ELASTIC_INDEX
  value: {{ .Values.commsy.elasticIndex | quote }}
- name: MESSENGER_TRANSPORT_DSN
  value: {{ .Values.commsy.messengerTransportDsn | quote }}
- name: LOCK_DSN
  value: {{ .Values.commsy.lockDsn | quote }}
{{- with .Values.commsy.extraEnv }}
{{ toYaml . }}
{{- end }}
{{- end -}}

{{- define "commsy.envFrom" -}}
{{- range .Values.commsy.existingSecrets }}
- secretRef:
    name: {{ . }}
{{- end }}
{{- end -}}

{{- define "commsy.volumeMounts" -}}
{{- if .Values.persistence.files.enabled }}
- name: files
  mountPath: /app/files
{{- if .Values.persistence.jwtOnFilesVolume }}
- name: files
  mountPath: /app/config/jwt
  subPath: jwt
{{- end }}
{{- end }}
{{- end -}}

{{- define "commsy.volumes" -}}
{{- if .Values.persistence.files.enabled }}
- name: files
  persistentVolumeClaim:
    claimName: {{ include "commsy.filesClaim" . }}
{{- end }}
{{- end -}}

{{- define "commsy.affinity" -}}
{{- if and .Values.persistence.files.enabled .Values.podAffinityForSharedVolume (eq .Values.persistence.files.accessMode "ReadWriteOnce") }}
affinity:
  podAffinity:
    requiredDuringSchedulingIgnoredDuringExecution:
      - labelSelector:
          matchLabels:
            {{- include "commsy.selectorLabels" . | nindent 12 }}
        topologyKey: kubernetes.io/hostname
{{- end }}
{{- end -}}
