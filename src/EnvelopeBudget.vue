<template>
	<div :class="$vuetify.breakpoint.width > 700 ? 'bigfont' : 'smallfont'">
		<v-row no-gutters>
			<v-col cols="3"
				md="2"
				class="header"
				align-self="end">
				Account
			</v-col>
			<v-col :cols="$vuetify.breakpoint.width > 410 ? 7 : 9" md="9" class="header">
				<v-row no-gutters>
					<v-col v-for="column in filterCols(data.cols, $vuetify.breakpoint.name)" :key="column.id">
						<v-row no-gutters>
							<v-col cols="3" v-if="$vuetify.breakpoint.width > 960">
								&nbsp;
							</v-col>
							<v-col :cols="$vuetify.breakpoint.width > 960 ? 3 : 4"
								align="right"
								align-self="end"
								class="header">
								Budget
							</v-col>
							<v-col :cols="$vuetify.breakpoint.width > 960 ? 3 : 4"
								align="right"
								align-self="end"
								class="header">
								<div>{{ column }}</div>
								Actual
							</v-col>
							<v-col :cols="$vuetify.breakpoint.width > 960 ? 3 : 4"
								align="right"
								align-self="end"
								class="header">
								Bucket
							</v-col>
						</v-row>
					</v-col>
				</v-row>
			</v-col>
			<v-col cols="2"
				md="1"
				align="center"
				align-self="end"
				v-if="$vuetify.breakpoint.width > 410"
				class="header">
				Goal
			</v-col>
		</v-row>
		<v-row v-for="(row, rowindex) in data.report"
			:key="row.id"
			:class="rowindex % 2 == 0 ? 'evenrow' : 'oddrow'"
			no-gutters>
			<v-col cols="3"
				md="2"
				v-if="row.cols"
				:class="row.prefix ? 'pl-3 accountname' : 'header accountname'">
				{{ row.acctname }}
			</v-col>
			<v-col cols="12" v-else class="header accountname">
				{{ row.acctname }}
			</v-col>
			<v-col v-if="row.cols" :cols="$vuetify.breakpoint.width > 410 ? 7 : 9" md="9">
				<v-row no-gutters>
					<v-col v-for="column in filterCols(row.cols, $vuetify.breakpoint.name)" :key="column.id">
						<v-row no-gutters>
							<v-col cols="3" v-if="$vuetify.breakpoint.width > 960">
								&nbsp;
							</v-col>
							<v-col cols="$vuetify.breakpoint.width > 960 ? 3 : 4" align="right" :class="column.budgetamt && column.budgetamt.amount.bn.isNegative() ? 'negativered value' : 'value'">
								{{ column.budgettext }}
							</v-col>
							<v-col cols="$vuetify.breakpoint.width > 960 ? 3 : 4" align="right" :class="column.actualamt && column.actualamt.amount.bn.isNegative() ? 'negativered value' : 'value'">
								{{ column.actualtext }}
							</v-col>
							<v-col cols="$vuetify.breakpoint.width > 960 ? 3 : 4" align="right" :class="column.bucketamt && column.bucketamt.amount.bn.isNegative() ? 'negativered value' : 'value'">
								{{ column.buckettext }}
							</v-col>
						</v-row>
					</v-col>
				</v-row>
			</v-col>
			<v-col cols="2"
				v-if="row.cols && $vuetify.breakpoint.width > 410"
				md="1"
				align="center"
				class="value">
				GOAL
			</v-col>
		</v-row>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { toAmount, fromAmount } from './ledgerparser2.js'
import { BigNumber } from 'bignumber.js'
import moment from '@nextcloud/moment'
export default {
	name: 'EnvelopeBudget',
	props: [],
	data() {
		return {
			maindata: [],
			budgetdata: [],
			data: []
		}
	},

	async mounted() {
		const postMainOptions = { query: ['not:^Assets:OnBudget'], options: [['historical'], ['monthly'], ['file', 'main.ledger.txt'], ['begin', moment().subtract(4, 'months').format('YYYY-MM')], ['end', 'next month']] }
		const mainDataPromise = axios.post('/apps/hledger/api/1/balance', postMainOptions)
		const postBudgetOptions = { query: ['not:^Budget:Assets:OnBudget'], options: [['historical'], ['monthly'], ['file', 'b.ledger.txt'], ['begin', moment().subtract(4, 'months').format('YYYY-MM')], ['end', 'next month']] }
		const budgetDataPromise = axios.post('/apps/hledger/api/1/balance', postBudgetOptions)

		this.maindata = (await mainDataPromise).data
		this.budgetdata = (await budgetDataPromise).data

		this.data = this.computeBudgetReport(this.maindata, this.budgetdata)
	},

	methods: {
		filterCols(cols, bpname) {
			if (!cols) {
				return cols
			}
			let num = 4
			switch (bpname) {
			case 'xs':
				num = 1
				break
			case 'sm':
				num = 1
				break
			case 'md':
				num = 2
				break
			case 'lg':
				num = 3
				break
			case 'xl':
				num = 4
				break
			}
			return cols.slice(cols.length - num)
		},
		computeBudgetReport(main, budget) {
			const mindex = []
			const bindex = []
			const mcols = []
			const bcols = []
			let accounts = []
			let cols = []
			if (main.length <= 1 || main[0].length <= 2 || budget.length <= 1 || budget[0].length <= 2) {
				return []
			}
			for (let i = 1; i < main.length; i++) {
				mindex[main[i][0]] = i
				accounts[main[i][0]] = 1
			}
			for (let i = 1; i < budget.length; i++) {
				const acct = budget[i][0].replace(/^Budget:/g, '')
				bindex[acct] = i
				accounts[acct] = 1
			}
			for (let i = 1; i < main[0].length; i++) {
				mcols[main[0][i]] = i
				cols[main[0][i]] = 1
			}
			for (let i = 1; i < budget[0].length; i++) {
				bcols[budget[0][i]] = i
				cols[budget[0][i]] = 1
			}
			accounts = [...Object.keys(accounts)]
			accounts.sort()

			cols = [...Object.keys(cols)]
			cols.sort()

			let report = []
			const bigNumberZero = BigNumber(0)
			let prefix = null
			for (let i = 0; i < accounts.length; i++) {
				if (prefix === null || !accounts[i].startsWith(prefix)) {
					const parts = accounts[i].split(':')
					prefix = ''
					if (accounts.length > i + 1) {
						const nextparts = accounts[i + 1].split(':')
						for (let j = 0; j < parts.length; j++) {
							if (parts[j] === nextparts[j]) {
								prefix = parts.slice(0, j + 1).join(':') + ':'
							} else {
								break
							}
						}
					}
					if (!prefix) {
						prefix = parts.slice(0, parts.length - 1).join(':')
						if (prefix) {
							prefix = prefix + ':'
						}
					}
					if (report.length > 0 && !report[report.length - 1].cols) {
						report = report.slice(0, report.length - 1)
					}
					if (prefix) {
						report.push({ acctname: prefix })
					}
				}
				const thisacctname = accounts[i].substring(prefix.length)
				const row = { cols: [], prefix, acctname: thisacctname }
				let addRow = false
				for (let j = 1; j < cols.length; j++) {
					let budgetVal = BigNumber(0)
					let budgetAmt = null
					let actualVal = BigNumber(0)
					let actualAmt = null
					let bucketVal = BigNumber(0)
					let bucketAmt = null
					if (accounts[i] in bindex && cols[j] in bcols) {
						budgetAmt = fromAmount(budget[bindex[accounts[i]]][bcols[cols[j]]])
						bucketAmt = fromAmount(budget[bindex[accounts[i]]][bcols[cols[j]]])
						if (bucketAmt !== null && bucketAmt.amount !== null) {
							budgetVal = budgetAmt.amount.bn
							bucketVal = bucketAmt.amount.bn
						}
					}
					if (accounts[i] in mindex && cols[j] in mcols) {
						actualAmt = fromAmount(main[mindex[accounts[i]]][mcols[cols[j]]])
						const mainAmt = fromAmount(main[mindex[accounts[i]]][mcols[cols[j]]])
						// Choose the one that has currency defined
						if (bucketAmt === null || (!bucketAmt.amount.currency && mainAmt.amount.currency)) {
							bucketAmt = mainAmt
						}
						if (mainAmt !== null && mainAmt.amount !== null) {
							actualVal = actualAmt.amount.bn
							bucketVal = bucketVal.minus(mainAmt.amount.bn)
						}
					}
					if (accounts[i] in bindex && cols[j - 1] in bcols) {
						const previousBudgetAmt = fromAmount(budget[bindex[accounts[i]]][bcols[cols[j - 1]]])
						// Choose the one that has currency defined
						if (budgetAmt === null || (!budgetAmt.amount.currency && previousBudgetAmt.amount.currency)) {
							budgetAmt = previousBudgetAmt
						}
						if (previousBudgetAmt !== null && previousBudgetAmt.amount !== null) {
							budgetVal = budgetVal.minus(previousBudgetAmt.amount.bn)
						}
					}
					if (accounts[i] in mindex && cols[j - 1] in mcols) {
						const previousMainAmt = fromAmount(main[mindex[accounts[i]]][mcols[cols[j - 1]]])
						// Choose the one that has currency defined
						if (actualAmt === null || (!actualAmt.amount.currency && previousMainAmt.amount.currency)) {
							actualAmt = previousMainAmt
						}
						if (previousMainAmt !== null && previousMainAmt.amount !== null) {
							actualVal = actualVal.minus(previousMainAmt.amount.bn)
						}
					}
					row.cols.push({ budgettext: '', budgetamt: null, actualtext: '', actualamt: null, buckettext: '', bucketamt: null })
					if (budgetAmt !== null && budgetAmt.amount !== null) {
						budgetAmt.amount.bn = budgetVal
						row.cols[row.cols.length - 1].budgettext = toAmount(budgetAmt)
						row.cols[row.cols.length - 1].budgetamt = budgetAmt
						if (!budgetVal.isEqualTo(bigNumberZero)) {
							addRow = true
						}
					}
					if (actualAmt !== null && actualAmt.amount !== null) {
						actualAmt.amount.bn = actualVal
						row.cols[row.cols.length - 1].actualtext = toAmount(actualAmt)
						row.cols[row.cols.length - 1].actualamt = actualAmt
						if (!actualVal.isEqualTo(bigNumberZero)) {
							addRow = true
						}
					}
					if (bucketAmt !== null && bucketAmt.amount !== null) {
						bucketAmt.amount.bn = bucketVal
						row.cols[row.cols.length - 1].buckettext = toAmount(bucketAmt)
						row.cols[row.cols.length - 1].bucketamt = bucketAmt
						if (!bucketVal.isEqualTo(bigNumberZero)) {
							addRow = true
						}
					}
				}
				if (addRow) {
					report.push(row)
				}
			}
			cols = cols.slice(1)
			return { report, cols, accounts }
		}
	},

	watch: {
	},

	computed: {
	}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.arrow-right-bold {
	color: red;
}

.header {
	font-weight: bold;
}

.accountname {
	overflow-wrap: break-word;
}

.value {
	text-align: right;
}

.bigfont {
	font-size: 11pt;
}

.smallfont {
	font-size: 9pt;
}

.bigspace {
	width: 40px;
}

.negativered {
	color: red;
}

.evenrow {
	background-color: #DCDCDC;
}

.oddrow {
	background-color: white;
}

.evenrow:hover {
	background-color: yellow;
}

.oddrow:hover {
	background-color: yellow;
}
</style>
