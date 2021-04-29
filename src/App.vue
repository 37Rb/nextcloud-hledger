<template>
	<Content :class="{'icon-loading': loading}" app-name="hledger">
		<AppNavigation>
			<template #list>
				<AppNavigationItem title="Budget" icon="icon-toggle-filelist" />
				<AppNavigationItem title="Income Statement" icon="icon-clippy" />
				<AppNavigationItem title="Balance Sheet" icon="icon-edit" />
			</template>
			<AppNavigationSettings>
				Example Settings
			</AppNavigationSettings>
		</AppNavigation>
		<AppContent>
			Hello World!
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
		return {
			loading: false,
			message: 'Initial Message',
			report: [
				['A', 'B', 'C'],
				[1, 2, 3],
				[4, 5, 6],
			],
		}
	},
	computed: {},
	methods: {},
}
</script>
