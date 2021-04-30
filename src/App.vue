<template>
	<Content :class="{'icon-loading': loading}" app-name="hledger">
		<AppNavigation>
			<template #list>
				<AppNavigationItem title="Budget" icon="icon-toggle-filelist" />
				<AppNavigationItem title="Income Statement" icon="icon-clippy" />
				<AppNavigationItem title="Balance Sheet" icon="icon-edit" />
			</template>
			<template #footer>
				<AppNavigationSettings>
					Example Settings
				</AppNavigationSettings>
			</template>
		</AppNavigation>
		<AppContent>
			<table class="hledger-data">
				<tr v-for="row in report" :key="row.id">
					<td v-for="cell in row" :key="cell.id" :class="{ outline: outlineRow(row[0]) }">
						{{ cell }}
					</td>
				</tr>
			</table>
		</AppContent>
	</Content>
</template>
<script>
import Content from '@nextcloud/vue/dist/Components/Content'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationSettings from '@nextcloud/vue/dist/Components/AppNavigationSettings'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import '@nextcloud/dialogs/styles/toast.scss'
export default {
	name: 'App',
	components: {
		Content,
		AppNavigation,
		AppNavigationItem,
		AppNavigationSettings,
		AppContent,
	},
	data() {
		return OCP.InitialState.loadState('hledger', 'state')
	},
	computed: {},
	methods: {
		outlineRow(x) {
			return ['Account', 'Total:'].includes(x)
		},
	},
}
</script>
